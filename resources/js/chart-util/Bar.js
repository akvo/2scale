import {
    Easing,
    Color,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import { formatNumber } from "../util.js";
import sum from "lodash/sum";
import sortBy from "lodash/sortBy";
import reverse from "lodash/reverse";

const Bar = (title, subtitle, props, data, extra) => {
    let values = [];
    let labels = [];
    data = !data ? [] : data;
    if (data.length > 0) {
        data = sortBy(data, "name");
        data = reverse(data);
        values = data.map((x) => x.value);
        labels = data.map((x) => x.name);
    }
    let avg = 0;
    if (values.length > 0) {
        avg = sum(values) / values.length;
        avg = avg < 100 ? true : false;
    }
    const text_style = TextStyle;
    let option = {
        ...Color,
        title: {
            text: title,
            subtext: subtitle,
            right: "center",
            top: "20px",
            ...text_style,
        },
        grid: {
            top: "15%",
            left: "20%",
            show: true,
            label: {
                color: "#222",
                fontFamily: "MarkPro",
                ...text_style,
            },
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
        yAxis: {
            type: "category",
            data: labels,
            axisLabel: {
                color: "#222",
                fontFamily: "MarkPro",
                ...text_style,
            },
            axisTick: {
                alignWithLabel: true,
            },
        },
        xAxis: {
            type: "value",
        },
        series: [
            {
                data: values,
                type: "bar",
                label: {
                    formatter: function (params) {
                        return formatNumber(params.data);
                    },
                    position: "insideLeft",
                    show: true,
                    color: "#222",
                    fontFamily: "MarkPro",
                    padding: 5,
                    borderRadius: avg ? 20 : 5,
                    backgroundColor: "rgba(0,0,0,.3)",
                    textStyle: {
                        ...text_style.textStyle,
                        color: "#fff",
                    },
                },
            },
        ],
        ...Color,
        ...backgroundColor,
        ...Easing,
        ...extra,
    };
    return option;
};

export default Bar;
