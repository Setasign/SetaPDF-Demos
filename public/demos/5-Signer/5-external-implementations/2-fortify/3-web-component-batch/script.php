<?php

if ($_SERVER['SERVER_NAME'] !== 'localhost' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    throw new Exception('This demo must run on localhost or HTTPS.');
}

$path = substr($_SERVER['PHP_SELF'], 0, -strlen(basename(__FILE__)));
$controllerPath = 'https://' . $_SERVER['HTTP_HOST'] . $path . 'controller.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SetaPDF-Signer meets Fortify</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pure-css-loader@3.3.3/dist/css-loader.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@peculiar/fortify-webcomponents@4/dist/peculiar/peculiar.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Open Sans", "Arial", sans-serif;
            font-size: 14px;
            height: 100vh;
            color: rgb(64, 72, 79);
            margin: 0;
            padding: 0;

            /* adjust some colors in the Fortify webcomponent */
            --peculiar-color-footer-rgb: 255, 255, 255;
            --peculiar-color-footer-text-rgb: 0, 0, 0;
        }

        #signatureControlsPanel, #previewContainer, #signButtonContainer, #fortifyContainer, #downloadButtonContainer {
            height:100%;
        }

        #previewContainer, #signButtonContainer, #fortifyContainer, #downloadButtonContainer {
            border: 0;
            float: left;
            width: 50%;
        }

        #signButtonContainer, #fortifyContainer, #downloadButtonContainer {
            border-right: 1px solid rgb(234, 237, 242);
            border-top: 1px solid rgb(234, 237, 242);
            border-bottom: 1px solid rgb(234, 237, 242);
        }

        #signButtonContainer, #downloadButtonContainer {
            padding: 34px 50px 46px;
        }

        h4 {
            font-size: 17px;
            margin-top: 0;
            padding-top: 0;
        }

        button.btnContinue, button.btnCancel {
            justify-content: center;
            border-radius: 3px;
            padding: 0 26px;
            height: 40px;
            float: right;
            cursor: pointer;
            transition: color 200ms;
        }

        button.btnContinue {
            color: #ffffff;
            border: 1px solid rgb(10, 190, 101);
            background-color: rgb(10, 190, 101);
        }

        button.btnContinue:hover {
            color: #9ddd97;
        }

        button.btnCancel {
            color: rgb(109, 125, 135);
            background-color: #ffffff;
            border: 1px solid rgb(182, 195, 204);
            float: none;
        }

        button.btnCancel:hover {
            color: rgb(182, 195, 204);
        }

        button.btnContinue:focus, button.btnCancel:focus {
            box-shadow: 0 4px 10px 0 rgba(0, 0, 0, 0.1);
            outline:none;
        }

        label.checkbox {
            display: block;
            margin: 5px 0;
            margin-left: 20px;
        }

        label.checkbox input[type=checkbox] {
            position: absolute;
            margin-left: -20px;
        }

        .loader-default::after {
            border-color: rgb(13, 132, 255);
            border-left-color: transparent;
        }
    </style>
</head>
<body>
<div id="loader" class="loader loader-default is-active" data-text="Loading..."></div>
<div id="outdated" style="display:none;">Your browser is outdated.</div>

<div id="signatureControlsPanel" style="display:none;">
    <div id="previewContainer"></div>
    <div id="signButtonContainer">
        <h4>Signature Settings</h4>
        <label class="checkbox" for="useAIA">
            <input type="checkbox" name="useAIA" id="useAIA" checked="checked"/>
            Embedded certificates fetched from the <a href="http://www.pkiglobe.org/auth_info_access.html" target="_blank">AIA extension</a>.
        </label>

        <label class="checkbox" for="useTimestamp">
            <input type="checkbox" name="useTimestamp" id="useTimestamp" checked="checked" />
            Embedded timestamp if adobe timestamp extension is available in certificate.
        </label>

        <button id="signBtn" class="btnContinue">Start and choose certificate</button>
    </div>
    <div id="fortifyContainer" style="display: none;height:100%;"></div>
    <div id="downloadButtonContainer" style="display: none;">
        <p>The documents were successfully signed.</p>
        <p id="extraCerts"></p>
        <p id="tsUrl"></p>
        <button id="resetBtn" class="btnCancel">Restart</button>
    </div>
</div>

<script type="text/javascript">
    var controllerPath = '<?=$controllerPath?>';
    document.addEventListener("DOMContentLoaded", function () {
        function loadScript(src, module) {
            return new Promise(function (resolve, reject) {
                var script = document.createElement('script');
                script.src = src;
                script.type = 'text/javascript';
                script.onload = resolve.bind(null, true);
                script.onerror = reject;
                if (typeof module !== 'undefined') {
                    if (module) {
                        script.type = 'module';
                    } else {
                        script.noModule = true;
                    }
                }
                document.head.appendChild(script);
            });
        }

        try {
            loadScript('https://cdn.jsdelivr.net/npm/@peculiar/fortify-webcomponents@4/dist/peculiar/peculiar.esm.js', true)
                .then(function () {return loadScript('https://verify.ink/webcomponent/index.js', true)})
                .then(function () {return loadScript('js/main.js')})
                .catch(function (e) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('outdated').style.display = '';
                    console.error(e);
                });
        } catch (e) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('outdated').style.display = '';
            console.error(e);
        }
    });
</script>
</body>
</html>