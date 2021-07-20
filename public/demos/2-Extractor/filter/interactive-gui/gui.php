<?php
if (!isset($dpi, $file, $basePath)) {
    die();
}
$script = $_SERVER['SCRIPT_NAME'];
?>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/jquery/2.2.4/jquery.min.js"
            integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
            crossorigin="anonymous"
    ></script>
    <script type="text/javascript"
            src="https://cdn.jsdelivr.net/jquery.blockui/2.70.0/jquery.blockUI.min.js"
            integrity="sha256-9wSYpoBdTOlj3azv4n74Mlb984+xKfTS7dhcYRqSqMA="
            crossorigin="anonymous"
    ></script>

    <link rel="stylesheet"
          type="text/css"
          href="https://cdn.jsdelivr.net/jquery.jcrop/0.9.12/css/jquery.Jcrop.min.css"
          integrity="sha256-/fCoT6hQHsrj1J/wn7oNqgWmtm9alQ2QRwWm2B0Fo1o="
          crossorigin="anonymous"/>
    <script src="https://cdn.jsdelivr.net/jquery.jcrop/0.9.12/js/jquery.Jcrop.min.js"
            integrity="sha256-ZxCBLDyBkvv5I47GMz1THCbcQ00JR0BvWlqWUEXupKI="
            crossorigin="anonymous"
    ></script>

</head>
<body>
<table>
    <tr>
        <td>
            <fieldset class="pageCount" style="border: 0;"></fieldset>
            <div class="imageContainer" style="border: 1px solid #d3d3d3;"></div>
        </td>
        <td style="vertical-align: top; padding: 5px;">
            <div class="extractedText"></div>
        </td>
    </tr>
</table>

<script type="text/javascript">
    $(function() {
        var actualPage = 1,
            isLoading = false,
            dpi = <?=$dpi?>,
            file = '<?=$file?>',
            pageFormats, jcrop;

        $.blockUI.defaults.message = '<img src="<?=$basePath?>layout/img/ajax-loader-big.gif" />';
        $.extend($.blockUI.defaults.css, {
            backgroundColor: 'transparent',
            border: 'none',
            color: '#fff'
        });

        var initImage = function () {
            if (jcrop) {
                jcrop.destroy();
            }

            $('div.extractedText').empty();
            $('.imageContainer').empty()
                // note: this controller generates an image of the pdf + page number
                .html('<img class="demoImage" src="<?=$script?>' + '?action=generateImagePreview&file=' + file + '&page=' + actualPage + '"/>');

            $('img.demoImage')
                .load(function () {
                    $('.demoImage').Jcrop({
                        onSelect: function(c) {
                            if (isLoading) {
                                return;
                            }

                            var height = pageFormats[actualPage - 1][1];
                            var dpiFactor = 1/72 * dpi;
                            c.y = height - c.y / dpiFactor;
                            c.y2 = height - c.y2 / dpiFactor;
                            c.x = c.x / dpiFactor;
                            c.x2 = c.x2 / dpiFactor;

                            $.blockUI();
                            $.ajax({
                                url : '<?=$script?>',
                                type : 'GET',
                                cache : false,
                                data: 'action=extract&file=' + file + '&page=' + actualPage + '&data[x1]=' + c.x + '&data[y1]=' + c.y + '&data[x2]=' + c.x2 + '&data[y2]=' + c.y2,
                            }).done(function(result) {
                                try {
                                    var extractedText = $('div.extractedText');
                                    extractedText.empty();

                                    extractedText.html('<h3>Script Output:</h3><pre>' + result.result + '</pre>');
                                } catch(error) {
                                    console.error(error);
                                }

                                $.unblockUI();
                            }).fail(function(error) {
                                console.error(error.responseText);
                                $.unblockUI();
                            });
                        },
                        onRelease: function() {
                            $('a[href="#code"]').addClass('disabled');

                            var extractedText = $('div.extractedText');
                            extractedText.empty();
                        }
                    }, function () {
                        jcrop = this;
                    });
                });
        };

        if (isLoading) {
            return;
        }
        isLoading = true;

        actualPage = 1;
        $('div.extractedText').empty();
        $.blockUI();
        $.ajax({
            url: '<?=$script?>',
            type: 'GET',
            cache: false,
            data: 'action=fetchPageCountAndFormats&file=' + file,
        }).done(function(result) {
            isLoading = false;
            $.unblockUI();

            var fieldset = $('fieldset.pageCount');
            fieldset.empty();

            pageFormats = result.pageFormats;
            var pageNumberSelect = '<label for="pageNumber" style="margin-right: 5px;">Page number:</label><select name="data[page]" id="pageNumber">';
            for (var i = 1; i <= result.pageCount; i++) {
                pageNumberSelect += '<option value="' + i + '"'+ (i == actualPage ? ' selected="selected"' : '') +'>'
                    + i + '</option>';
            }
            pageNumberSelect += '</select>';

            fieldset.html(pageNumberSelect);

            $('select#pageNumber', fieldset).change(function() {
                actualPage = $(this).val();
                initImage();
            });

            initImage();
        }).fail(function(error) {
            isLoading = false;
            console.error(error.responseText);
            $.unblockUI();
        });
    });
</script>
</body>
</html>