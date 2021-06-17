import createElement from "./app";
const axios = window.axios;
import { getCharts, getCards } from "./charts.js";

axios.get("/api/rsr/impact-reach/uii").then((res) => {
    const data = res.data;
    $("main").append(data.map((x) => <div class="row">{x.group}</div>));
});
