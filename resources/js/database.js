import { db, storeDB } from "./dexie";
const axios = window.axios;
const _ = require("lodash");

Date.prototype.addHours = function (h) {
    this.setTime(this.getTime() + h * 60 * 60 * 1000);
    return this;
};

const table = db.databases;

const expired = localStorage.getItem("database-expired");
if (!expired || expired < new Date()) {
    table.clear();
    localStorage.setItem("database-expired", new Date().addHours(2));
}

const fetchData = (endpoint) => {
    return new Promise((resolve, reject) => {
        axios
            .get("/api/datatables" + endpoint)
            .then((res) => {
                storeDB({
                    table: table,
                    data: { name: endpoint, data: res.data },
                    key: { name: endpoint },
                });
                resolve(res.data);
            })
            .catch((error) => {
                reject(error);
            });
    });
};

const endpoint = $("meta[name='data-url']").attr("content");
// load from dixie if exist
const loadData = async (endpoint) => {
    const res = await table.get({ name: endpoint });
    if (res === undefined) {
        return fetchData(endpoint);
    }
    return res.data;
};
const getdata = loadData(endpoint);

const createRows = (datas, rowType, colspan = false, res, index = 1) => {
    let html = "";
    let questions = [];
    let data = datas;
    if (rowType === "head" && colspan) {
        data = datas.qgroups;
    }
    // normal
    if (rowType === "body" || rowType === "head") {
        html += "<tr>";
        html +=
            rowType === "head" && colspan
                ? "<td rowspan='2'>ID</td>"
                : "<td>" + index + "</td>";
        data.forEach((d, i) => {
            if (rowType === "head" && colspan) {
                questions = datas.questions.filter(
                    (x) => x.question_group_id === d.id
                );
                // don't render group head if doesn't have child question
                // note, UII1 - BoP question group id 41 - (TO BE FILLED BY PrC) Conversion Factor Information
                // (prod: doesnt has question, check to flow, that question group is not available)
                if (!questions.length) {
                    return;
                }
            }
            let classname = i < 10 ? "default-hidden" : "";
            classname = d.text ? classname + "" : classname + " bg-light-grey";
            let colspanOpt = colspan
                ? "colspan='" + questions.length + "'"
                : "";
            html +=
                rowType === "head"
                    ? "<th class='" + classname + "' " + colspanOpt + ">"
                    : "<th class='" + classname + "'>";
            html +=
                rowType === "head"
                    ? d.name
                        ? d.name
                        : ""
                    : d.text
                    ? d.text
                    : "";
            html += rowType === "head" ? "</th>" : "</td>";
        });
        html += "</tr>";
    }
    // repeat group
    if (rowType === "bodyRepeat") {
        let repeats = data.filter(
            (x) => x.repeat === 1 && x.repeat_answers.length > 0
        );
        if (repeats.length > 0) {
            // refactor repeat answers by datapoint_id and repeat_index
            let repeat_answers = [];
            repeats.forEach((x) =>
                x.repeat_answers.forEach((y) => repeat_answers.push(y))
            );
            let datapoints = _.values(
                _.groupBy(repeat_answers, (x) => x.datapoint_id)
            );
            let answers = _.values(
                _.groupBy(datapoints[0], (x) => x.repeat_index)
            );

            // starting creating row
            answers.forEach((repeat, y) => {
                // remap repeat answer to meet question length (fill with null)
                if (repeat.length !== res.questions.length) {
                    repeat = res.questions.map((x) => {
                        let find = _.find(
                            repeat,
                            (y) => y.question_id === x.question_id
                        );
                        return find === undefined ? null : find;
                    });
                }
                html += "<tr>";
                html += "<td>" + index + "</td>"; // id column
                repeat.forEach((d, i) => {
                    let classname = i < 10 ? "default-hidden" : "";
                    classname =
                        d !== null
                            ? d.text
                                ? classname + ""
                                : classname + " bg-light-grey"
                            : classname + " bg-light-grey";
                    html += "<td class='" + classname + "'>";
                    html += d !== null ? (d.text ? d.text : "") : "";
                    html += "</td>";
                });
                html += "</tr>";
            });
        }
    }
    // question header
    if (rowType === "head" && colspan) {
        html += "<tr>";
        data.forEach((g, i) => {
            questions = datas.questions.filter(
                (x) => x.question_group_id === g.id
            );
            questions.forEach((d, i) => {
                let classname = i < 10 ? "default-hidden" : "";
                classname = d.text
                    ? classname + ""
                    : classname + " bg-light-grey";
                html += "<td class='" + classname + "'>";
                html += d.text ? d.text : "";
                html += "</td>";
            });
        });
        html += "</tr>";
    }
    return html;
};

const createTable = (id, data, rowType, res = []) => {
    let tType =
        rowType === "body" || rowType === "bodyRepeat" ? "body" : "head";
    let html = "<t" + tType + ">";
    if (rowType === "body" || rowType === "bodyRepeat") {
        let index = 1;
        data.forEach((r, i) => {
            html += createRows(r.data, rowType, false, res, index);
            index++;
        });
    }
    if (rowType === "head") {
        html += createRows(data, rowType, true);
    }
    html += "</t" + tType + ">";
    $(id).append(html);
    return true;
};

// create repeat group
$(document).on("click", "a.gtabs", function () {
    let gid = $(this).attr("dataId");
    let question_group_id = gid.split("-")[1];
    let dtId = "datatables-" + gid + "";
    if (gid !== "gtabs-parent") {
        // generate table
        loadData(endpoint)
            .then((res) => {
                // filter repeat group data

                // one repeat group tab
                // res['qgroups'] = res.qgroups.filter(x => x.repeat === 1);
                // res['questions'] = res.questions.filter(x => x.repeat === 1);

                // each repeat group tab
                res["qgroups"] = res.qgroups.filter(
                    (x) => x.id == question_group_id
                );
                res["questions"] = res.questions.filter(
                    (x) => x.question_group_id == question_group_id
                );

                res["datapoints"] = res.datapoints.map((dp) => {
                    let qids = res.questions.map((x) => x.question_id);
                    let data = dp.data.filter((d) =>
                        qids.includes(d.question_id)
                    );
                    dp["data"] = data;
                    return dp;
                });

                let table =
                    '<table id="' +
                    dtId +
                    '" class="table table-bordered" style="width:100%" cellspacing="0"></table>';
                $("#" + gid + "").html(table);
                createTable("#" + dtId + "", res, "head");
                return res;
            })
            .then((res) => {
                createTable("#" + dtId + "", res.datapoints, "bodyRepeat", res);
                return res;
            })
            .then((res) => {
                if (res) {
                    datatableOptions("#" + dtId + "", res);
                }
                return true;
            });
    }
});

const createaNavTab = (id, name, active = false) => {
    name =
        name.toLowerCase() === "all data" ? name : `${name} - (Repeat group)`;
    let cactive = active ? "active" : "";
    let ids = "gtabs-" + id;
    let tabs = '<li class="nav-item">';
    tabs +=
        '<a \
                class="nav-link gtabs ' +
        cactive +
        '" \
                dataId="' +
        ids +
        '" \
                id="' +
        ids +
        '-tab" \
                href="#' +
        ids +
        '" \
                data-toggle="tab" \
                role="tab" \
                aria-controls="' +
        ids +
        '" \
                aria-selected="' +
        active +
        '" \
            >';
    tabs += name.toUpperCase();
    tabs += "</a>";
    tabs += "</li>";
    $("#grouptabs").append(tabs);

    // tab content
    if (id !== "parent") {
        let tabContents =
            '<div \
                                class="tab-pane" \
                                id="' +
            ids +
            '" \
                                role="tabpanel" \
                                aria-labelledby="' +
            ids +
            '-tab">\
                            </div>';
        $("#datatableWrapper").append(tabContents);
    }
    return true;
};

getdata
    .then((res) => {
        // createTable(res.questions, "head");
        let tabAllDataContent =
            '<div class="tab-pane active" id="gtabs-parent" role="tabpanel" aria-labelledby="gtabs-parent-tab"></div>';
        $("#datatableWrapper").append(tabAllDataContent);
        let table =
            '<table id="datatables" class="table table-bordered" style="width:100%" cellspacing="0"></table>';
        $("#gtabs-parent").append(table);

        // load qgroups tabs
        let groups = res.qgroups.filter((x) => x.repeat === 1);
        if (groups.length !== 0) {
            let ultabs =
                "<ul id='grouptabs' class='nav nav-tabs' style='margin-bottom:15px;'></ul>";
            $("#grouptabsWrapper").append(ultabs);
            createaNavTab("parent", "All Data", true);

            // one repeat group tab
            // createaNavTab('qgroup', 'Repeat Group Data');

            // each repeat group tab
            groups.forEach((x) => {
                createaNavTab(x.id, x.name);
            });
        }

        // new table headers with question groups
        createTable("#datatables", res, "head");
        return res;
    })
    .then((res) => {
        createTable("#datatables", res.datapoints, "body");
        return res;
    })
    .then((res) => {
        if (res) {
            datatableOptions("#datatables", res);
        }
        return true;
    })
    .then(() => {
        // change footer style to relative
        $("#loader-spinner").remove();
        $(".tmp-footer")[0].style.position = "relative";
    });

const datatableOptions = (id, res) => {
    $("" + id + " thead tr")
        .clone(true)
        .appendTo("#example thead");
    $("" + id + " thead tr:eq(1) th").each(function (i) {
        var title = $(this).text();
        $(this).html('<input type="text" placeholder="Search"/>');
        $("input", this).on("keyup change", () => {
            if (table.column(i).search() !== this.value) {
                table.column(i).search(this.value).draw();
            }
        });
    });
    // generate filename
    const filenameTemp = [];
    const optSelected = window.parent
        .$("#select-database-survey option:selected")
        .text()
        .trim();
    filenameTemp.push(optSelected);
    const countrySelected = window.parent
        .$("#select-country-survey option:selected")
        .text()
        .trim();
    if (!countrySelected.toLowerCase().includes("select country")) {
        filenameTemp.push(countrySelected);
    }
    const partnershipSelected = window.parent
        .$("#select-partnership-survey option:selected")
        .text()
        .trim();
    if (!partnershipSelected.toLowerCase().includes("select partnership")) {
        filenameTemp.push(partnershipSelected);
    }
    const dateSelected = window.parent.$(".datarange-picker").val().trim();
    filenameTemp.push(dateSelected);
    const filename = filenameTemp.join("_");
    let dtoptions = {
        dom: "Birftp",
        buttons: [
            {
                extend: "excelHtml5",
                filename: filename,
                customize: function (xlsx) {
                    // converts numbers to spreadsheet letter columns eg. 1 -> A
                    function getExcelColumn(num) {
                        let s = "",
                            t;
                        while (num > 0) {
                            t = (num - 1) % 26;
                            s = String.fromCharCode(65 + t) + s;
                            num = ((num - t) / 26) | 0;
                        }
                        return s || undefined;
                    }
                    //copy _createNode function from source
                    function _createNode(doc, nodeName, opts) {
                        var tempNode = doc.createElement(nodeName);
                        if (opts) {
                            if (opts.attr) {
                                $(tempNode).attr(opts.attr);
                            }
                            if (opts.children) {
                                $.each(opts.children, function (key, value) {
                                    tempNode.appendChild(value);
                                });
                            }
                            if (opts.text !== null && opts.text !== undefined) {
                                tempNode.appendChild(
                                    doc.createTextNode(opts.text)
                                );
                            }
                        }
                        return tempNode;
                    }
                    const sheet = xlsx.xl.worksheets["sheet1.xml"];
                    const mergeCells = $("mergeCells", sheet);
                    mergeCells[0].children[0].remove(); // remove merge cell 1st row
                    const rows = $("row", sheet);
                    rows[0].children[0].remove(); // clear header cell
                    const customHeaderRowIndex = 1;
                    // iterate by qgroups
                    let startColumnIndex = 2; // 1 or A used by ID table header
                    let endColumnIndex = 0;
                    res.qgroups.forEach((qg) => {
                        const filterQuestions = res.questions.filter(
                            (q) => q.question_group_id === qg.id
                        );
                        startColumnIndex = endColumnIndex
                            ? endColumnIndex + 1
                            : startColumnIndex;
                        endColumnIndex =
                            startColumnIndex + (filterQuestions.length - 1);
                        const startColumn = getExcelColumn(startColumnIndex);
                        const endColumn = getExcelColumn(endColumnIndex);
                        const newCell = `${startColumn}${customHeaderRowIndex}`;
                        const mergedCell = `${startColumn}${customHeaderRowIndex}:${endColumn}${customHeaderRowIndex}`;
                        // start
                        rows[0].appendChild(
                            _createNode(sheet, "c", {
                                attr: {
                                    t: "inlineStr",
                                    r: newCell, //address of new cell start
                                    s: 2, // style - https://www.datatables.net/reference/button/excelHtml5
                                },
                                children: {
                                    row: _createNode(sheet, "is", {
                                        children: {
                                            row: _createNode(sheet, "t", {
                                                text: qg.name,
                                            }),
                                        },
                                    }),
                                },
                            })
                        );
                        // set new cell merged
                        mergeCells[0].appendChild(
                            _createNode(sheet, "mergeCell", {
                                attr: {
                                    ref: mergedCell, // merge address / colspan
                                },
                            })
                        );
                        mergeCells.attr("count", mergeCells.attr("count") + 1);
                    });
                },
            },
            "copy",
            "colvis",
        ],
        scrollX: true,
        scrollY: "75vh",
        height: 400,
        paging: false,
        fixedHeader: true,
        scrollCollapse: true,
    };
    let hideColumns = {
        columnDefs: [
            { targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], visible: true },
            { targets: "_all", visible: false },
        ],
    };
    dtoptions =
        res.questions.length > 10
            ? { ...dtoptions, ...hideColumns }
            : dtoptions;
    $(id).DataTable(dtoptions);
    // Material Design example
    $("#datatables_wrapper")
        .find("label")
        .each(function () {
            $(this).parent().append($(this).children());
        });
    $("#datatables_wrapper .dataTables_filter")
        .find("input")
        .each(function () {
            const $this = $(this);
            $this.attr("placeholder", "Search");
            $this.removeClass("form-control-sm");
        });
    $("#datatables_wrapper .dataTables_length").addClass("d-flex flex-row");
    $("#datatables_wrapper .dataTables_filter").addClass("md-form");
    $("#datatables_wrapper select").removeClass(
        "custom-select custom-select-sm form-control form-control-sm"
    );
    $("#datatables_wrapper select").addClass("mdb-select");
    $("#datatables_wrapper .dataTables_filter").find("label").remove();
};
