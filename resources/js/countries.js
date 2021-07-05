import createElement from "./app";
import axios from "axios";
import { popupFormatter, visualMap } from "./chart-util/chart-style";
import dataStore from "./store";

const mapName = "africa";

const testArray = ["Kenya", "Uganda", "Niger", "Nigeria"];
const testRandom = () => {
    const rand = Math.floor(Math.random() * testArray.length);
    return testArray[rand];
};

const updateOptions = () => {
    const { currentState } = dataStore;
    const options = {
        tooltip: {
            trigger: "item",
            showDelay: 0,
            transitionDuration: 0.2,
            formatter: popupFormatter,
        },
        visualMap: visualMap,
        series: [
            {
                type: "map",
                zoom: 1,
                room: true,
                aspectScale: 1,
                map: mapName,
                data: currentState.data,
            },
        ],
    };
    currentState.maps.setOption(options);
};

const handleButtonClick = () => {
    console.log(dataStore.currentState.data[0]);
    dataStore.update((s) => {
        s.data = [{ name: testRandom(), value: 4 }];
    });
    updateOptions();
};

$("main").append(
    <div class="row">
        <div class="col-md-12">
            <h2 class="responsive font-weight-bold text-center my-4">
                Reaching Targets
            </h2>
            <div id="maps" style="height:700px;"></div>
            <button onClick={handleButtonClick}>Test</button>
        </div>
    </div>
);

const createMaps = () => {
    const html = document.getElementById("maps");
    const myMap = echarts.init(html);
    dataStore.update((s) => {
        s.maps = myMap;
    });
    axios.get("/json/africa.geojson").then((res) => {
        echarts.registerMap(mapName, res.data);
        updateOptions();
    });
};

createMaps();
