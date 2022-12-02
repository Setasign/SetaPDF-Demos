(function() {
    // Some helper functions:
    function show(id, replacements) {
        replacements = replacements || {};
        let e = document.getElementById(id);
        if (!e.htmlTpl) {
            e.htmlTpl = e.innerHTML;
        }

        if (Object.keys(replacements).length > 0) {
            let tpl = e.htmlTpl;
            for (let k in replacements) {
                if (!replacements.hasOwnProperty(k)) {
                    continue;
                }

                let v = replacements[k];
                tpl = tpl.replace('{' + k + '}', v);
            }
            e.innerHTML = tpl;
        }

        e.style.display = '';
    }

    function hide(id) {
        document.getElementById(id).style.display = 'none';
    }

    // some helper functions to work with typed arrays
    function toHex(buffer) {
        let buf = new Uint8Array(buffer),
            splitter = "",
            res = [],
            len = buf.length;

        for (let i = 0; i < len; i++) {
            let char = buf[i].toString(16);
            res.push(char.length === 1 ? "0" + char : char);
        }
        return res.join(splitter);
    }

    function fromHex(hexString) {
        let res = new Uint8Array(hexString.length / 2);
        for (let i = 0; i < hexString.length; i = i + 2) {
            let c = hexString.slice(i, i + 2);
            res[i / 2] = parseInt(c, 16);
        }
        return res.buffer;
    }

    // we need some ajax
    function postRequest(url, params) {
        return new Promise(function(resolve, reject) {
            let xhr = new XMLHttpRequest();
            xhr.open("POST", url, true);
            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        resolve(xhr.responseText);
                    } else {
                        reject(xhr, xhr.status);
                    }
                }
            };

            xhr.onerror = (() => reject(xhr, xhr.status));
            xhr.send(params);
        });
    }

    // the main function
    async function main() {
        let ws = new WebcryptoSocket.SocketProvider({
            storage: await WebcryptoSocket.BrowserStorage.create(),
        });

        // Checks if end-to-end session is approved
        let handleChallenge = async () => {
            if (!await ws.isLoggedIn()) {
                const pin = await ws.challenge();
                // show PIN
                show('challengeExchange', {pin: pin});
                // ask to approve session
                try {
                    await ws.login();
                } catch (e) {
                    if (confirm('Challenge was not accepted. Retry?')) {
                        await handleChallenge();
                    }
                }
                hide('challengeExchange');
            }
        };

        let providersSelect = document.getElementById('providersSelect'),
            certificatesSelect = document.getElementById('certificatesSelect'),
            signBtn = document.getElementById('signBtn'),
            downloadBtn = document.getElementById('downloadBtn');

        signBtn.disabled = true;
        downloadBtn.disabled = true;

        let init = () => {
            show('signatureControlsPanel');
            ws.cardReader
                .on("insert", () => updateProviders())
                .on("remove", () => updateProviders());
            updateProviders();
        };

        let updateProviders = async () => {
            const info = await ws.info();
            let selected = false;
            let currentProviderId = providersSelect.value;

            providersSelect.length = 0;

            if (!info.providers.length) {
                const option = document.createElement("option");
                option.textContent = "No providers";
                option.setAttribute("value", "");
                option.disabled = true;
                providersSelect.appendChild(option);
                providersSelect.dispatchEvent(new Event('change'));
                return;
            }

            for (const provider of info.providers) {
                const option = document.createElement("option");
                option.setAttribute("value", provider.id);
                option.textContent = provider.name;
                if (currentProviderId === provider.id) {
                    option.setAttribute("selected", "selected");
                    selected = true;
                }
                providersSelect.appendChild(option);
            }

            if (!selected) {
                providersSelect.firstElementChild.setAttribute("selected", "selected");
            }

            providersSelect.dispatchEvent(new Event('change'));
        };

        providersSelect.addEventListener('change', () => updateCertificates());

        let certs, provider;
        let updateCertificates = async () => {
            if (providersSelect.value === '') {
                certificatesSelect.length = 0;
                const option = document.createElement("option");
                option.textContent = "No certificates";
                option.setAttribute("value", "");
                option.disabled = true;
                certificatesSelect.appendChild(option);

                return;
            }

            provider = await ws.getCrypto(providersSelect.value);
            if (!(await provider.isLoggedIn())) {
                try {
                    await provider.login();
                } catch (e) {
                    // you may map e.code to a more meaningful message. A list of codes is available
                    // here: https://github.com/PeculiarVentures/fortify-web/blob/master/src/sagas/error.js
                    alert(e.message);
                    providersSelect.length = 0;
                    await updateProviders();
                    return;
                }
            }

            certs = [];
            let certIds = await provider.certStorage.keys();
            certIds = certIds.filter((id) => {
                const parts = id.split("-");
                return parts[0] === "x509";
            });

            let keyIds = await provider.keyStorage.keys();
            keyIds = keyIds.filter((id) => (id.split("-")[0] === "private"));

            const extractCommonName = (name) => {
                let reg = /CN=([^,]+),?/i,
                    res = reg.exec(name);
                return res ? res[1] : "Unknown";
            };

            for (const certId of certIds) {
                for (const keyId of keyIds) {
                    if (keyId.split("-")[2] === certId.split("-")[2]) {
                        try {
                            const cert = await provider.certStorage.getItem(certId);
                            certs.push({
                                id: certId,
                                item: cert,
                                name: extractCommonName(cert.subjectName),
                                pem: await provider.certStorage.exportCert('pem', cert),
                                privateKey: await provider.keyStorage.getItem(keyId)
                            });
                        } catch (e) {
                            console.error(`Cannot get certificate ${certId} from CertificateStorage. ${e.message}`);
                        }
                    }
                }
            }

            const now = new Date();
            certs = certs
                .filter((cert) => (cert.item.notBefore < now && now < cert.item.notAfter))
                .sort((a, b) => (a.name.localeCompare(b.name, undefined, {sensitivity: 'base'})));

            certificatesSelect.length = 0;
            certs.forEach((cert, index) => {
                const option = document.createElement("option"),
                    issuer = extractCommonName(cert.item.issuerName);

                option.setAttribute("value", index);
                option.textContent = cert.name + ' (' + issuer + '; '
                    + ' not before:' + cert.item.notBefore.toLocaleString() + '; '
                    + ' not after:' + cert.item.notAfter.toLocaleString() + ')';
                certificatesSelect.appendChild(option);
            });

            signBtn.disabled = (certs.length === 0);
        };

        let lastId = null;
        let sign = async () => {
            let cert = certs[certificatesSelect.value];

            try {
                let startResponseText = await postRequest(
                    'controller.php?action=start',
                    JSON.stringify({
                        certificate: cert.pem,
                        useAIA: document.getElementById('useAIA').checked,
                        useTimestamp: document.getElementById('useTimestamp').checked
                    })
                );
                let startJson = JSON.parse(startResponseText),
                    privateKey = cert.privateKey;

                const message = fromHex(startJson.dataToSign);
                const alg = {
                    name: privateKey.algorithm.name,
                    hash: "SHA-256",
                };

                let signature = await provider.subtle.sign(alg, privateKey, message);
                let completeResponseText = await postRequest(
                    'controller.php?action=complete',
                    JSON.stringify({signature: toHex(signature)})
                );
                let completeJson = JSON.parse(completeResponseText);
                lastId = completeJson.id;
                downloadBtn.disabled = false;
                window.open('controller.php?action=download&id=' + lastId);
            } catch (error) {
                console.info(error);
                alert('An error occured: ' + error.responseText);
            }
        };

        downloadBtn.addEventListener('click', () => window.open('controller.php?action=download&id=' + lastId));
        signBtn.addEventListener('click', () => sign());

        ws.connect("127.0.0.1:31337")
            .on("error", (e) => {
                hide('loading');
                show('fortifyNotReachable');
                console.error(e);
            })
            .on("listening", (e) => {
                hide('loading');

                handleChallenge()
                    .then(() => {
                        return ws.isLoggedIn();
                    })
                    .then((isLoggedIn) => {
                        // was it successfully?
                        if (!isLoggedIn) {
                            show('notLoggedIn');
                            return;
                        }

                        init();
                    }, (error) => {
                        console.error(error)
                    });
            });
    }

    //noinspection JSIgnoredPromiseFromCall
    main();
})();