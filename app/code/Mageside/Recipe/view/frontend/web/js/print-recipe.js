define(
    [
        'jquery'
    ],
    function ($) {
        var minFontSize = 9;
        var curentFontSize = 16;
        var maxFontSize = 22;

        $('#decfont').click(function () {
            if (curentFontSize > minFontSize) {
                curentFontSize -= 1;
                var allElems = $('.print-text');

                document.getElementById("decfont").className = "action print-menu primary";
                document.getElementById("incfont").className = "action print-menu";

                for (var counter = 0; counter < allElems.length; counter++) {
                    var tempCurSize = parseInt($(allElems[counter]).css("fontSize"));
                    if (tempCurSize > minFontSize) {
                        tempCurSize -= 1;
                        $(allElems[counter]).css('font-size', tempCurSize + 'px');
                    }
                }
            }
        });

        $('#incfont').click(function () {
            if (curentFontSize < maxFontSize) {
                curentFontSize += 1;
                var allElems = $('.print-text');

                document.getElementById("incfont").className = "action print-menu primary";
                document.getElementById("decfont").className = "action print-menu";

                for (var counter = 0; counter < allElems.length; counter++) {
                    var tempCurSize = parseInt($(allElems[counter]).css("fontSize"));
                        tempCurSize += 1;
                        $(allElems[counter]).css('font-size', tempCurSize + 'px');
                }
            }
        });

        $('#hideimg').click(function () {
            document.getElementById("product-image").className = "noPrintContent page-title-wrapper product";
            document.getElementById("hideimg").className = "action print-menu primary";
            document.getElementById("showimg").className = "action print-menu";
        });

        $('#showimg').click(function () {
            document.getElementById("product-image").className = "onPrintContent page-title-wrapper product";
            document.getElementById("showimg").className = "action print-menu primary";
            document.getElementById("hideimg").className = "action print-menu";
        });

        $('#hidesum').click(function () {
            document.getElementById("description").className = "noPrintContent description print-text";
            document.getElementById("hidesum").className = "action print-menu primary";
            document.getElementById("showsum").className = "action print-menu";
        });

        $('#showsum').click(function () {
            document.getElementById("description").className = "onPrintContent description print-text";
            document.getElementById("showsum").className = "action print-menu primary";
            document.getElementById("hidesum").className = "action print-menu";
        });
    }
)
