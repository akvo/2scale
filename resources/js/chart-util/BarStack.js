import {
    Easing,
    Color,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import sum from "lodash/sum";

const BarStack = (title, subtitle, data, extra) => {
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
    let option = {
        ...Color,
        title: {
            text: title,
            subtext: subtitle,
            left: "center",
            top: "20px",
            ...TextStyle,
        },
        legend: {
            data: data.legends,
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
            top: "23px",
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
        xAxis: data.xAxis.map((x) => {
            return {
                ...x,
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
            };
        }),
        series: data.series,
        ...Color,
        ...backgroundColor,
        ...Easing,
        ...extra,
    };
    return option;
};

export default BarStack;
