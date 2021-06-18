import {
    Easing,
    Color,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import _ from "lodash";

const LineStack = (data, extra) => {
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
                    position: "inside",
                    color: "#a43332",
                },
                stack: "t",
                type: "line",
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
            left: "auto",
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
            formatter: "{b}: {c}",
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

export default LineStack;