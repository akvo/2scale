import {
    Color,
    Easing,
    Legend,
    TextStyle,
    backgroundColor,
    Icons,
} from "./chart-style.js";
import { flatten, flattenChildren } from "../util.js";
import uniq from "lodash/uniq";
import sumBy from "lodash/sumBy";
import sortBy from "lodash/sortBy";

const createLinks = (data, links) => {
    if (data.children.length > 0) {
        data.children.forEach((x) => {
            if (x.datapoints !== undefined) {
                let child = data.childrens.filter((y) => y.code === x.code);
                let target = child.length > 0 ? child[0].name : x.name;
                if (x.datapoints.length > 0) {
                    links.push({
                        source: data.name,
                        target: x.name,
                        target_en: target,
                        value: x.datapoints.length,
                    });
                }
            } else {
                links.push({
                    source: data.name,
                    target: x.name,
                    target_en: target,
                });
            }
            createLinks(x, links);
        });
    }
    return links;
};

const SanKey = (title, subtitle, data, extra) => {
    let list = [];
    let links = [];
    if (data) {
        list = data.filter((x) => x.children.length > 0);
        list = flattenChildren(list); // flatten the translated children not childrens
        list = list.map((x) => x.name);
        list = uniq(list);
        list = list.map((x) => {
            return { name: x };
        });
        data.forEach((x) => {
            links = [...links, ...createLinks(x, [])];
        });
        // let otherandall = links.filter(x => x.target === "All of the above" || x.target === "Other");
        let otherandall = links.filter(
            (x) => x.target_en === "All of the above" || x.target_en === "Other"
        );
        if (otherandall.length > 1) {
            links = links.map((x) => {
                // if (x.target === "All of the above" || x.target === "Other") {
                if (
                    x.target_en === "All of the above" ||
                    x.target_en === "Other"
                ) {
                    let parent = x.source.split(" (")[0];
                    return {
                        ...x,
                        target: x.target + " (" + parent + ")",
                    };
                }
                return x;
            });
            list = links.map((x) => x.target);
            list = [...uniq(links.map((x) => x.source)), ...list];
            list = list.map((x) => {
                return { name: x };
            });
        }
        links = sortBy(links, "value");
    }
    const text_style = TextStyle;
    let option = {
        title: {
            text: title,
            subtext: subtitle,
            left: "center",
            top: "20px",
            ...text_style,
        },
        tooltip: {
            trigger: "item",
            triggerOn: "mousemove",
            backgroundColor: "#f2f2f2",
            formatter: function (par) {
                let name = par.data.name;
                if (par.dataType === "edge") {
                    name = par.data.source.split("(")[0];
                    name += "> " + par.data.target.split("(")[0];
                    name += " :" + par.data.value;
                }
                if (par.dataType === "node") {
                    name += ":" + par.value;
                }
                return name;
            },
            padding: 5,
            borderRadius: 5,
            position: [30, 50],
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
        series: [
            {
                top: "20%",
                type: "sankey",
                layout: "none",
                focusNodeAdjacency: "allEdges",
                data: list,
                links: links,
                nodeGap: 5,
                label: {
                    formatter: function (params) {
                        let name = params.name;
                        let value = links.find((x) => x.target === name);
                        if (value === undefined) {
                            value = links.filter((x) => x.source === name);
                            value = sumBy(value, "value");
                        } else {
                            value = value.value;
                        }
                        name = name.split("(")[0];
                        name = name.split("e.g")[0];
                        name = name.split("/")[0];
                        return name + "(" + value + ")";
                    },
                    color: "#222",
                    fontFamily: "MarkPro",
                    ...text_style,
                },
                lineStyle: {
                    curveness: 0.5,
                    color: "rgba(0,0,0,.3)",
                },
            },
        ],
        ...Color,
    };
    return option;
};

export default SanKey;
