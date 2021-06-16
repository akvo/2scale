import {
    Color,
    Easing,
    Legend,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import maxBy from "lodash/maxBy";
import sum from "lodash/sum";
import { formatNumber } from "../util.js";

const Radar = (title, subtitle, data, extra) => {
    let values = data.length > 0 ? data.map((x) => x.value) : [];
    let indicator = [];
    if (data.length > 0) {
        indicator = data.map((x) => {
            return {
                name: x.name,
                current: x.value,
                max: maxBy(data, "value").value,
            };
        });
    }
    if (sum(values) === 0) {
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
    let option = {
        title: {
            text: title,
            subtext: subtitle,
            left: "center",
            top: "20px",
            ...TextStyle,
        },
        toolbox: {
            show: true,
            orient: "horizontal",
            left: "right",
            top: "bottom",
            feature: {
                saveAsImage: {
                    type: "jpg",
                    title: "save image",
                    icon: Icons.saveAsImage,
                    backgroundColor: "#ffffff",
                },
            },
            backgroundColor: "#ffffff",
        },
        radar: {
            indicator: indicator,
            radius: 120,
            startAngle: 90,
            splitNumber: 4,
            name: {
                formatter: function (value, indicator) {
                    return value + "\n USD " + formatNumber(indicator.current);
                },
                textStyle: {
                    ...text_style.textStyle,
                    fontSize: 12,
                },
            },
            axisLine: {
                lineStyle: {
                    color: "rgba(255, 255, 255, 0.5)",
                },
            },
            splitLine: {
                lineStyle: {
                    color: "rgba(255, 255, 255, 0.5)",
                },
            },
        },
        series: [
            {
                type: "radar",
                areaStyle: {
                    color: "#009fe2",
                    opacity: 0.2,
                },
                lineStyle: {
                    color: "#009fe2",
                },
                itemStyle: {
                    color: "#009fe2",
                },
                data: [
                    {
                        value: values,
                    },
                ],
            },
        ],
    };
    return option;
};

export default Radar;
