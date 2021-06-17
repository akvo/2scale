import {
    Color,
    Easing,
    Legend,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import sumBy from "lodash/sumBy";

const Pie = (title, subtitle, data, extra, roseType = false) => {
    data = !data ? [] : data;
    let total = { name: "total", value: 0 };
    let labels = [];
    if (data.length > 0) {
        data = data.map((x) => {
            let n = x.name.split("(")[0];
            return {
                ...x,
                name: n,
            };
        });
        data = data;
        labels = data.map((x) => x.name);
        total = {
            ...total,
            value: sumBy(data, "value"),
        };
    }
    let rose = {};
    if (roseType) {
        rose = { roseType: roseType };
    }
    if (sumBy(data, "value") === 0) {
        return {
            title: {
                text: title,
                subtext: "No Data",
                left: "center",
                top: "20px",
                ...TextStyle,
            },
        };
    }
    const text_style = TextStyle;
    const legend = Legend;
    let option = {
        ...Color,
        title: {
            text: title,
            subtext: subtitle,
            right: "center",
            top: "20px",
            ...text_style,
        },
        tooltip: {
            show: true,
            trigger: "item",
            formatter: "{b}",
            padding: 5,
            backgroundColor: "#f2f2f2",
            textStyle: {
                ...text_style.textStyle,
                fontSize: 12,
            },
        },
        toolbox: {
            show: true,
            orient: "horizontal",
            left: "right",
            top: "top",
            backgroundColor: "#FFF",
        },
        series: [
            {
                name: title,
                type: "pie",
                right: "center",
                radius: roseType ? ["20%", "70%"] : ["40%", "90%"],
                label: {
                    normal: {
                        formatter: "{d}%",
                        show: true,
                        position: roseType ? "outside" : "inner",
                        padding: 5,
                        borderRadius: 100,
                        backgroundColor: roseType
                            ? "rgba(0,0,0,.5)"
                            : "rgba(0,0,0,.3)",
                        textStyle: {
                            ...text_style.textStyle,
                            color: "#fff",
                        },
                    },
                    emphasis: {
                        fontSize: 12,
                        formatter: "{c} ({d} %)",
                        position: "center",
                        show: true,
                        backgroundColor: "#f2f2f2",
                        borderRadius: 5,
                        padding: 10,
                        ...text_style,
                    },
                },
                labelLine: {
                    normal: {
                        show: true,
                    },
                },
                data: data,
                ...rose,
            },
            {
                data: [total],
                type: "pie",
                right: "center",
                radius: roseType ? ["0%", "20%"] : ["0%", "30%"],
                label: {
                    normal: {
                        show: true,
                        position: "center",
                        textStyle: {
                            ...text_style.textStyle,
                            fontWeight: "bold",
                            color: "#495057",
                        },
                    },
                },
                color: ["#f1f1f5"],
            },
        ],
        legend: {
            data: labels,
            ...legend,
        },
        ...Color,
        ...backgroundColor,
        ...Easing,
        ...extra,
    };
    return option;
};

export default Pie;