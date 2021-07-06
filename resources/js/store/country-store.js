import { Store } from "pullstate";

const countryStore = new Store({
    data: [],
    maps: false,
    mapData: [],
    country: false,
    counts: [],
    charts: [],
    chartList: [],
});

export default countryStore;
