import { Store } from "pullstate";

const countryStore = new Store({
    data: [],
    maps: false,
    mapData: [],
    country: false,
    counts: [],
    charts: [],
    chartList: [],
    filters: [],
    valuePath: null,
    selectedPath: null,
});

export default countryStore;
