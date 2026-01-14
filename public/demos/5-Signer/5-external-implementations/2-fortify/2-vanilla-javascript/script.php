<?php

if ($_SERVER['SERVER_NAME'] !== 'localhost' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    throw new Exception('This demo must run on localhost or HTTPS.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SetaPDF-Signer meets Fortify</title>
    <style>
        body {
            font-family: Tahoma,Verdana,Segoe,sans-serif;
            font-size: 14px;
        }

        #signatureControlsPanel {
            margin-top: 1em;
        }
    </style>
</head>
<body>

<div id="loading">Loading...</div>
<div id="outdated" style="display:none;">Your browser is outdated.</div>
<div id="fortifyNotReachable" style="display:none;">Fortify is not running. <a href="https://fortifyapp.com/" target="_blank">Install</a> and start the app on your computer and reload this demo. Note: Most browsers need to run this script in https.</div>
<div id="challengeExchange" style="display:none;">Please compare this pin <b>{pin}</b> with the one that Fortify displays and confirm.</div>
<div id="notLoggedIn" style="display: none;">You're not logged in with Fortify.</div>

<div id="signatureControlsPanel" style="display: none;">

    <select id="providersSelect"><option>Loading...</option></select>
    <select id="certificatesSelect"><option>Loading...</option></select>
    <br />

    <input type="checkbox" name="useAIA" id="useAIA" checked="checked"/><label for="useAIA">Embedded certificates fetched from the AIA extension (only HTTP, .cer/.der (no .p7c support), no validation is done).</label><br />
    <input type="checkbox" name="useTimestamp" id="useTimestamp" checked="checked" /><label for="useTimestamp">Embedded timestamp if adobe timestamp extension is available in certificate.</label><br />

    <button id="signBtn" disabled="disabled">Sign Dummy File</button>
    <button id="downloadBtn" disabled="disabled">Download</button>

    <script type="text/javascript">
        if (!window.Promise) {
            document.getElementById('outdated').style.display = '';
        } else {
            document.addEventListener("DOMContentLoaded", function(event) {
                function loadScript(src) {
                    return new Promise(function(resolve, reject) {
                        var script = document.createElement('script');
                        script.src = src;
                        script.type = 'text/javascript';
                        script.onload = resolve.bind(null, true);
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }

                loadScript('https://cdn.jsdelivr.net/npm/@babel/polyfill@7.8.3/dist/polyfill.min.js')
                    .then(function () {return loadScript('https://cdn.jsdelivr.net/npm/asmcrypto.js@2.3.2/asmcrypto.all.es5.min.js')})
                    .then(function () {return loadScript('https://rawcdn.githack.com/indutny/elliptic/60489415e545efdfd3010ae74b9726facbf08ca8/dist/elliptic.min.js')})
                    .then(function () {return loadScript('https://cdn.jsdelivr.net/npm/webcrypto-liner@1.2.2/build/webcrypto-liner.shim.min.js')})
                    .then(function () {return loadScript('https://cdn.jsdelivr.net/npm/protobufjs@6.8.8/dist/protobuf.min.js')})
                    .then(function () {return loadScript('https://cdn.jsdelivr.net/npm/@webcrypto-local/client@1.1.0/build/webcrypto-socket.min.js')})
                    .then(function () {return loadScript('js/main.js')})
                    .catch(function(e) {
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('outdated').style.display = '';
                        console.error(e);
                    });
            });
        }
    </script>
</div>

</body>
</html>
