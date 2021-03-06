import {
    Easing,
    Color,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import _ from "lodash";
import { formatNumber } from "../util.js";

const BarStack = (data, extra) => {
    if (!data) {
        return {
            title: {
                text: "No Data",
                subtext: "",
                left: "center",
                top: "20px",
                ...TextStyle,
            },
        };
    }
    let xAxis = _.uniq(data.map((x) => x.group));
    let legends = _.uniq(data.map((x) => x.name));
    let series = _.chain(data)
        .groupBy("name")
        .map((x, i) => {
            if (i.toLowerCase() === "pending") {
                return {
                    name: i,
                    label: {
                        formatter: function (params) {
                            let total = data
                                .filter(
                                    (d) => d.name.toLowerCase() === "achieved"
                                )
                                .find((d) => d.group === params.name);
                            if (total?.value) {
                                let sum = total.value + params.value;
                                return formatNumber(sum);
                            }
                            return "";
                        },
                        show: true,
                        position: "top",
                        color: "#a43332",
                    },
                    stack: "t",
                    type: "bar",
                    barWidth: 50,
                    data: x.map((v) => v.value),
                    itemStyle: {
                        color: "transparent",
                        borderType: "dashed",
                        borderColor: "#000",
                    },
                };
            }
            return {
                name: i,
                label: {
                    show: true,
                    position: "inside",
                    formatter: function (params) {
                        const { value } = params;
                        return value ? formatNumber(value) : 0;
                    },
                },
                barWidth: 50,
                stack: "t",
                type: "bar",
                data: x.map((v) => v.value),
            };
        })
        .value();
    series = _.sortBy(series, "name");
    let option = {
        ...Color,
        legend: {
            data: _.sortBy(legends).filter(
                (l) => l.toLowerCase() !== "pending"
            ),
            icon: "circle",
            top: "0px",
            left: "center",
            align: "auto",
            orient: "horizontal",
            textStyle: {
                fontFamily: "MarkPro",
                fontWeight: "bold",
                fontSize: 12,
            },
        },
        grid: {
            top: "50px",
            left: "auto",
            right: "auto",
            bottom: "50px",
            borderColor: "#ddd",
            borderWidth: 0.5,
            show: true,
            label: {
                color: "#222",
                fontFamily: "MarkPro",
            },
        },
        tooltip: {
            trigger: "item",
            formatter: function (params) {
                const { name, value, seriesName } = params;
                return `${name} : ${
                    value ? formatNumber(value) : 0
                } (${seriesName})`;
            },
            backgroundColor: "#ffffff",
            ...TextStyle,
        },
        toolbox: { show: false },
        yAxis: [
            {
                type: "value",
                axisLabel: {
                    inside: true,
                    backgroundColor: "#f2f2f2",
                    padding: 5,
                    fontFamily: "MarkPro",
                    fontSize: 12,
                    color: "#000",
                },
                axisLine: { show: false },
                splitLine: {
                    lineStyle: {
                        color: "#c4c4c4",
                    },
                },
            },
        ],
        xAxis: {
            data: xAxis,
            type: "category",
            axisLine: {
                lineStyle: {
                    color: "#c4c4c4",
                },
            },
            axisLabel: {
                fontFamily: "MarkPro",
                fontSize: 12,
                color: "#000",
            },
        },
        series: series,
        ...Color,
        ...backgroundColor,
        ...Easing,
        ...extra,
    };
    return option;
};

export default BarStack;
