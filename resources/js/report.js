const axios = window.axios;
import { getMaps, getCharts, getCards } from "./charts.js";
import { renderRsrTableTemplate, renderRsrTable } from "./rsrDatatables.js";

const baseurl = $("meta[name=path]").attr("content");

const renderReportForm = () => {
    axios
        .get(baseurl + "/api/flow/partnerships")
        .then(res => {
            res.data.forEach(c => {
                $("#country-level").append('<option value="'+c.id+'">'+c.name+' (CTL)</option>');
                if (c.childrens.length > 0) {
                    c.childrens.forEach(p => {
                        $("#partnership-level").append('<option class="partnerships ppp-'+c.id+'" value="'+p.id+'">'+p.name+' (PF)</option>');
                    });
                };
            });
            return res.data;
        }).then(res => {
            $(".partnerships").hide('fast');
        });
}

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
                            <option value="0" selected>Select Country</option>\
                          </select>\
                        </div>\
                        <div  class="col-4">\
                          <select id="partnership-level" class="form-control">\
                            <option value="0" selected>Select Partnership</option>\
                          </select>\
                        </div>\
                        <div  class="col-auto">\
                          <select id="year" class="form-control">\
                            <option value="0" selected>Year</option>\
                            <option value="2021">2021</option>\
                            <option value="2022">2022</option>\
                            <option value="2023">2023</option>\
                          </select>\
                        </div>\
                        <div  class="col-auto">\
                          <select id="selector" class="form-control">\
                            <option value="0" selected>Report Selector</option>\
                            <option value="1">Report 1</option>\
                            <option value="2">Report 2</option>\
                            <option value="3">Report 3</option>\
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
renderReportForm();

$("main").append("<div class='row' id='second-row'></div>");
/* second Row */
getCharts("report/total-activities", "second-row", "12");

// Rsr Datatables
renderRsrTableTemplate("datatables", "90%");
renderRsrTable(["0", "0"].join("/"), baseurl, "datatables");


$("#country-level").on("change", () => {
    let country_id = $("#country-level").val();
    $(".partnerships").hide('fast');
    $(".ppp-"+country_id).show('fast');
});

const showModalError = (response) => {
    $("#myModalAuthTitle").html("Error");
    $("#myModalAuthBody").html(
        '<div class="alert alert-warning" role="alert">'+response+'</div>'
    );
    $("#myModalAuth").modal({ backdrop: "static", keyboard: false });
}

$("#generate-word-report").on("click", () => {
    let country_id = $("#country-level").val();
    let partnership_id = $("#partnership-level").val();
    let year = $("#year").val();
    let selector = $("#selector").val();
    if (Number(country_id) === 0 || Number(year) === 0 || Number(selector) === 0) {
        showModalError("Country, Year, and Report Selector are required.");
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
        .get(baseurl + "/api/rsr/word-report/" + country_id + "/" + partnership_id + "/" + year + "/" + selector)
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
            console.error(err);
            $("#loader-spinner").remove();
            $("#myModalAuthTitle").html("Error");
            $("#myModalAuthBody").html(
                '<div class="alert alert-danger" role="alert">'+err.response.data+'</div>'
            );
            $("#myModalBtnClose").show();
        });
});
