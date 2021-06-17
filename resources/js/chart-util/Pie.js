import {
    Color,
    Easing,
    Legend,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import sumBy from "lodash/sumBy";

const Pie = (data, extra, roseType = false) => {
    data = !data ? [] : data;
    let total = { name: "", value: 0 };
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
    const { textStyle } = TextStyle;
    let option = {
        tooltip: {
            show: true,
            trigger: "item",
            formatter: "{b}",
            padding: 5,
            backgroundColor: "#f2f2f2",
            textStyle: {
                ...textStyle,
                fontSize: 12,
            },
        },
        series: [
            {
                name: "main",
                type: "pie",
                right: "center",
                radius: roseType ? ["20%", "70%"] : ["50%", "100%"],
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
                            ...textStyle,
                            color: "#fff",
                        },
                    },
                    emphasis: {
                        formatter: "{c} ({d} %)",
                        position: "center",
                        show: true,
                        padding: 5,
                        borderRadius: 100,
                        backgroundColor: "#f2f2f2",
                        textStyle: textStyle,
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
                radius: roseType ? ["0%", "20%"] : ["0%", "40%"],
                label: {
                    normal: {
                        show: true,
                        position: "center",
                        textStyle: {
                            ...textStyle,
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
            ...Legend,
        },
        ...Color,
        ...backgroundColor,
        ...Easing,
        ...extra,
    };
    return option;
};

export default Pie;
