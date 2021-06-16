import Bar from "./Bar";
import Maps from "./Maps";
import Pie from "./Pie";
import TreeMap from "./TreeMap";
import SanKey from "./SanKey";
import Radar from "./Radar";
import BarStack from "./BarStack";

const loadingState = {
    id: 1,
    name: "",
    units: "",
    description: "Loading",
    values: [{ id: 1, code: "", name: "Loading", value: 0 }],
};

const generateData = (col, line, height) => {
    return {
        column: col,
        line: line,
        style: {
            height: height,
        },
    };
};

const generateOptions = (type, title, subtitle, dataset, extra = {}) => {
    switch (type) {
        case "MAPS":
            return Maps(title, subtitle, dataset, extra);
        case "PIE":
            return Pie(title, subtitle, dataset, extra);
        case "ROSEPIE":
            return Pie(title, subtitle, dataset, extra, { roseType: "area" });
        case "TREEMAP":
            return TreeMap(title, subtitle, dataset, extra);
        case "SANKEY":
            return SanKey(title, subtitle, dataset, extra);
        case "RADAR":
            return Radar(title, subtitle, dataset, extra);
        case "BARSTACK":
            return BarStack(title, subtitle, dataset, extra);
        default:
            return Bar(title, subtitle, dataset, extra);
    }
};

export default { generateOptions, generateData };
