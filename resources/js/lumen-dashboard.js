/* Static */
const link = $("meta[name='link']").attr("content");

const renderPage = () => {
    if (!link) {
        const text = "Select UII to show Lumen visual.";
        $("main").append(
            <div class="row" style="margin-top: 30vh;">
                <div class="col-md-12">
                    <h5 class="text-center">{text}</h5>
                </div>
            </div>
        );
    } else {
        $("#lumen-visual-iframe").attr("src", link);
        document.getElementById("lumen-visual-iframe").height = "1000px";
        $("#lumen-visual-iframe")[0].style.paddingBottom = "75px";
        $(".tmp-footer")[0].style.position = "relative";
        $(".tmp-footer")[0].style.bottom = "60px";
    }
};

renderPage();
