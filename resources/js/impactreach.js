import createElement from "./app";
import { getCharts, getCards } from "./charts.js";
import { CountUp } from "countup.js";
const axios = window.axios;

let counts = [];

const uui = (x, idx) => {
    return x.childrens.map((c, i) => {
        let target = c.target_text || "";
        target = target.split("##").map((t) => {
            if (t === "number") {
                return (
                    <span style="font-weight:bold;color:#a43332;">
                        {c.target_value}
                    </span>
                );
            }
            return t;
        });
        const percentage =
            target.length > 1
                ? Math.ceil(c.target_value / c.actual_value)
                : null;
        if (target.length > 1) {
            counts.push({
                id: `percentage-${idx}-${i}`,
                val: percentage,
                suf: "%",
            });
        }
        counts.push({
            id: `achived-${idx}-${i}`,
            val: c.actual_value,
            suf: "",
        });
        return (
            <div class={`card col-md-${c.dimensions?.length ? 12 : 6}`}>
                <div class="card-body">
                    <div
                        class="uii-col uii-percentage"
                        id={`percentage-${idx}-${i}`}
                    >
                        {percentage ? 0 : " - "}
                    </div>
                    <div class="uii-col uii-detail">
                        <span style="font-weight:bold;">ACHIEVED: </span>
                        <span
                            style="font-weight:bold;color:#a43332;"
                            id={`achived-${idx}-${i}`}
                        >
                            0
                        </span>
                        <br />
                        <span style="font-weight:bold;">TARGET: </span>
                        {target.length > 1 ? target : " - "}
                    </div>
                </div>
            </div>
        );
    });
};

const groups = (x, i) => {
    return (
        <div class="row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    {x.group}
                </h3>
                <div class="row">{uui(x, i)}</div>
                <hr />
            </div>
        </div>
    );
};

axios
    .get("/api/rsr/impact-reach/uii")
    .then((res) => {
        const data = res.data;
        $("main").append(
            data.map((x, i) => {
                return groups(x, i);
            })
        );
        return counts;
    })
    .then((res) => {
        setTimeout(() => {
            res.forEach((x, i) => {
                const countUp = new CountUp(x.id, x.val, { suffix: x.suf });
                if (!countUp.error) {
                    countUp.start();
                }
            });
        }, 300);
    });
