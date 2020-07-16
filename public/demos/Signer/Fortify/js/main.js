(function() {
    // Some helper functions:
    function show(id) {
        document.getElementById(id).style.display = '';
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
        let lastId = null,
            fortifyComp = null;

        function initFortify () {
            // https://fortifyapp.com/developers/examples/certificate-management
            fortifyComp = document.createElement('peculiar-fortify-certificates');
            fortifyComp.style.height = '100%';
            fortifyComp.language = 'en';
            fortifyComp.filters = {
                //   onlySmartcards: false,
                expired: false,
                //   subjectDNMatch: 'apple',
                //   subjectDNMatch: new RegExp(/apple/),
                //   issuerDNMatch: 'demo',
                //   issuerDNMatch: new RegExp(/demo/),
                keyUsage: ['digitalSignature'],
                onlyWithPrivateKey: true
            };

            fortifyComp.addEventListener('cancel', function () {
                hide('fortifyContainer');
                show('signButtonContainer');
            });
            fortifyComp.addEventListener('continue', async function (event) {
                try {
                    show('loader');
                    document.getElementById('loader').setAttribute('data-text', 'Signing document');
                    let provider = await event.detail.server.getCrypto(event.detail.providerId);

                    let cert = await provider.certStorage.getItem(event.detail.certificateId);
                    let certPem = await provider.certStorage.exportCert('pem', cert);
                    let privateKey = await provider.keyStorage.getItem(event.detail.privateKeyId);

                    let startResponseText = await postRequest(
                        controllerPath + '?action=start',
                        JSON.stringify({
                            certificate: certPem,
                            useAIA: document.getElementById('useAIA').checked,
                            useTimestamp: document.getElementById('useTimestamp').checked
                        })
                    );
                    let startJson = JSON.parse(startResponseText);

                    if (startJson.extraCerts.length > 0) {
                        document.getElementById('extraCerts').innerHTML = startJson.extraCerts.length
                            + ' extra certificate(s) resolved and embedded through the '
                            + '<a href="http://www.pkiglobe.org/auth_info_access.html" target="_blank">AIA extension</a>.';
                    } else {
                        document.getElementById('extraCerts').innerHTML = 'No extra certificates were resolved.';
                    }

                    if (startJson.tsUrl) {
                        document.getElementById('tsUrl').innerHTML = 'Timestamp server located at <i>' + startJson.tsUrl
                            + '</i> was used.';
                    } else {
                        document.getElementById('tsUrl').innerHTML = 'No timestamp server found.';
                    }

                    const message = fromHex(startJson.dataToSign);
                    const alg = {
                        name: privateKey.algorithm.name,
                        hash: "SHA-256",
                    };

                    let signature = await provider.subtle.sign(alg, privateKey, message);
                    let completeResponseText = await postRequest(
                        controllerPath + '?action=complete',
                        JSON.stringify({signature: toHex(signature)})
                    );
                    let completeJson = JSON.parse(completeResponseText);
                    lastId = completeJson.id;

                    document.getElementById('preview').src = 'https://verify.ink/viewer/'
                        + '?url=' + encodeURIComponent(controllerPath + '?action=download&id=' + lastId)
                        + '&show-signature-if-present=false'
                        + '&notify-if-not-signed=false'
                        + '&sign=false'
                        + '&search=false'
                        + '&download=true';

                    hide('fortifyContainer');
                    hide('loader');
                    show('downloadButtonContainer');
                } catch (error) {
                    hide('loader');
                    console.info(error);
                    alert('An error occured.');
                }
            });

            document.getElementById('fortifyContainer').appendChild(fortifyComp);
        }

        function initPreview() {
            document.getElementById('preview').src = 'https://verify.ink/viewer/'
                + '?url=' + encodeURIComponent(controllerPath + '?action=preview')
                + '&show-signature-if-present=false'
                + '&notify-if-not-signed=false'
                + '&sign=false'
                + '&search=false'
                + '&download=true';
        }

        document.getElementById('signBtn').addEventListener('click', () => {
            if (!fortifyComp) {
                initFortify();
            }

            hide('signButtonContainer');
            show('fortifyContainer');
        });

        document.getElementById('resetBtn').addEventListener('click', () => {
            hide('downloadButtonContainer');
            initPreview();
            show('signButtonContainer');
        });

        initPreview();
        show('signatureControlsPanel');
        hide('loader');
    }

    //noinspection JSIgnoredPromiseFromCall
    main();

})();
