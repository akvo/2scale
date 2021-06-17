const axios = window.axios;
import { getMaps, getCharts, getCards } from "./charts.js";
import { renderRsrTableTemplate, renderRsrTable } from "./rsrDatatables.js";

const baseurl = $("meta[name=path]").attr("content");

$("main").append(
    '\
    <div class="row" id="first-row"> \
        <div class="col-md-12"> \
            <div class="card"> \
                <div class="card-header">Generate Word Report</div> \
                <div class="card-body"> \
                    <div class="d-flex justify-content-center align-items-center" id="loader-test"> \
                      <form class="row">\
                        <div class="col-auto">\
                          <select id="country-level" class="form-control">\
                            <option value="0" selected>Select Partnership</option>\
                            <option value="1">Burkina Faso</option>\
                            <option value="2">Cote d\'Ivoire</option>\
                            <option value="3">Egypt</option>\
                            <option value="4">Ethiopia</option>\
                            <option value="5">Ghana</option>\
                            <option value="6">Kenya</option>\
                            <option value="7">Mali</option>\
                            <option value="8">Niger</option>\
                            <option value="9">Nigeria</option>\
                            <option value="10">South Sudan</option>\
                          </select>\
                        </div>\
                        <div  class="col-auto">\
                          <select id="quarter" class="form-control">\
                            <option value="0" selected>Quarter</option>\
                            <option value="1">One</option>\
                            <option value="2">Two</option>\
                            <option value="3">Three</option>\
                          </select>\
                        </div>\
                        <div class="col-auto">\
                          <button id="generate-word-report" type="button" class="btn btn-sm btn-primary" style="margin-top: -0.0px; height:35px;">Download</button>\
                        </div>\
                      </form>\
                    </div> \
                </div> \
            </div> \
        </div> \
    </div>\
    <hr/>'
);

$("main").append("<div class='row' id='second-row'></div>");
/* second Row */
getCharts("report/total-activities", "second-row", "12");

// Rsr Datatables
renderRsrTableTemplate("datatables", "75%");
// renderRsrTableTemplate('datatables', '20%');
renderRsrTable(["0", "0"].join("/"), baseurl, "datatables");

$("#generate-word-report").on("click", () => {
    let country_id = $("#country-level").val();
    if (Number(country_id) === 0) {
        $("#myModalAuthTitle").html("Error");
        $("#myModalAuthBody").html(
            '<div class="alert alert-warning" role="alert">Please select Partnership</div>'
        );
        $("#myModalAuth").modal({ backdrop: "static", keyboard: false });
        return;
    }
    // Loading
    $("#myModalBtnClose").hide();
    $("#myModalAuthTitle").html("Please Wait");
    $("#myModalAuthBody").html(
        '<br>\
        <div class="d-flex justify-content-center" id="loader-spinner">\
            <div class="spinner-border text-primary loader-spinner" role="status">\
                <span class="sr-only">Loading...</span>\
            </div>\
        </div><br>'
    );
    $("#myModalAuth").modal({ backdrop: "static", keyboard: false });

    axios
        .get(baseurl + "/api/rsr/word-report/" + country_id + "/0/0/0")
        .then(res => {
            $("#loader-spinner").remove();
            $("#myModalAuthTitle").html("Report ready to download");
            $("#myModalAuthBody").html(
                '<a target="_blank" href="' +
                    res.data.link +
                    '">\
                    <button type="button" class="btn btn-primary"> Download Report</button>\
                </a>'
            );
            $("#myModalBtnClose").show();
        })
        .catch(err => {
            console.log("internal server error", err);
            $("#loader-spinner").remove();
            $("#myModalAuthTitle").html("Error");
            $("#myModalAuthBody").html(
                '<div class="alert alert-danger" role="alert">Please try again later!</div>'
            );
            $("#myModalBtnClose").show();
        });
});
