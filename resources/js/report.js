const axios = window.axios;
import { getMaps, getCharts, getCards } from "./charts.js";
import { renderRsrTableTemplate, renderRsrTable } from "./rsrDatatables.js";

const baseurl = $("meta[name=path]").attr("content");

$("main").append("<div class='row' id='first-row'></div>");
/* First Row */
getCharts("report/total-activities", "first-row", "12");

// Rsr Datatables
renderRsrTableTemplate("datatables", "75%");
// renderRsrTableTemplate('datatables', '20%');
renderRsrTable(["0", "0"].join("/"), baseurl, "datatables");
