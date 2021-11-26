import {
    Easing,
    Color,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import _ from "lodash";
import { formatNumber } from "../util.js";
import { param } from "jquery";

const BarGroup = (data, extra) => {
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
            return {
                name: i,
                label: {
                    show: true,
                    position: "top",
                    formatter: function (params) {
                        const { value } = params;
                        return value ? formatNumber(value) : 0;
                    },
                },
                type: "bar",
                barWidth: 200 / x.length,
                data: x.map((v) => v.value),
            };
        })
        .value();
    series = _.sortBy(series, "name");
    let option = {
        ...Color,
        legend: {
            data: _.sortBy(legends),
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
            left: "100px",
            right: "auto",
            bottom: "25px",
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
                return `<b>${name}</b><br>${seriesName}: ${
                    value ? formatNumber(value) : 0
                }`;
            },
            backgroundColor: "#ffffff",
            ...TextStyle,
        },
        toolbox: { show: false },
        yAxis: [
            {
                type: "value",
                axisLabel: {
                    inside: false,
                    backgroundColor: "#f2f2f2",
                    padding: 5,
                    fontFamily: "MarkPro",
                    fontSize: 12,
                },
                axisLine: { show: false },
            },
        ],
        xAxis: {
            data: xAxis,
            type: "category",
            axisLine: {
                lineStyle: {
                    color: "#ddd",
                },
            },
            axisLabel: {
                fontFamily: "MarkPro",
                fontSize: 12,
                color: "#222",
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

export default BarGroup;
