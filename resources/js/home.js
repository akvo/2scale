import createElement from "./app";
import generateCharts from "./chart-util";
import { getMaps, getCharts } from "./charts";
const axios = window.axios;

// Page title
$("main").append(
    <div>
        <div class="row" id="zero-row">
            <div class="col-md-12">
                <h2 class="responsive font-weight-bold text-center my-4">
                    Incubating and accelerating inclusive agribusiness in Africa
                </h2>
            </div>
        </div>
        <hr/>
    </div>
);

/* First Row */
$("main").append(
    <div>
        <div class="row" id="zero-row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    Countries of focus
                </h3>
                <div class="row" id="maps"></div>
            </div>
        </div>
        <div class="graphic">
            {/* <img src="/images/infographic.png" class="img img-fluid" /> */}
            <div class="popover-graphic-wrapper">
                <div
                    class="popover-graphic hidden-xs"
                    id="infoBlock1"
                    data-toggle="popover"
                    data-trigger="hover focus"
                    data-placement="top"
                    data-original-title="Producer organizations are formal rural collectives of growers and smallholder farmers. Their harvest goes to the private sector for trade, processing or makes it to targeted end-consumers directly."
                    data-content="2SCALE helps build capacity within these organizations and for their members through a.o. training, provision of credit, extension support, collective input purchasing and marketing activities. "
                    title="">
                </div>
                <div
                    class="popover-graphic hidden-xs"
                    id="infoBlock2"
                    data-toggle="popover"
                    data-trigger="hover focus"
                    data-placement="top"
                    data-content="2SCALE supports agribusiness clusters to become professional self-sustaining networks build around entrepreneurial business champions. The program helps to develop value chains by linking producers to end-consumers and drive growth. "
                    data-original-title="Agribusiness clusters are multi-actor networks that support producer organizations in accessing inputs, information and finance and connect them with local SMEs. "
                    title="">
                </div>
                <div
                    class="popover-graphic hidden-xs"
                    id="infoBlock3"
                    data-toggle="popover"
                    data-trigger="hover focus"
                    data-placement="top"
                    data-content="2SCALE strengthens these local entrepreneurs by providing technical and business support, enabling them to expand their businesses and help the agribusiness cluster grow. "
                    data-original-title="Small and medium-sized enterprises are local entrepreneurs such as traders, small processors, input suppliers or service providers. They play a key role in establishing a successful agribusiness cluster. "
                    title="">
                </div>
                <div id="infoBlock4"></div>
                <div
                    class="popover-graphic hidden-xs"
                    id="infoBlock5"
                    data-toggle="popover"
                    data-trigger="hover focus"
                    data-placement="top"
                    data-content="2SCALE organizes marketing and distribution activities to create consumer demand and to get nutritious food products to the base of the pyramid."
                    data-original-title="Based on consumer and market insights, 2SCALE supports farmer producers organizations and SMEs to develop new products for base of the pyramid markets."
                    title="">
                </div>
            </div>
        </div>
        <div class="graphic-bottom"></div>
        <div class="row" id="first-row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    2SCALE partners with business champions to leverage food
                    and nutrition security
                </h3>
            </div>
        </div>
        <hr />
        <div class="row" id="second-row"></div>
    </div>
);

const countChildren = (data) => {
    return data.map((d) => ({
        name: d.name,
        value: d.childrens.length,
    }));
};

generateCharts({
    type: "PIE",
    endpoint: "flow/partnerships",
    title: "Number of PPPs per Country",
    id: "ppp-per-country",
    parentId: "first-row",
});

generateCharts(
    {
        type: "DOUGHNUT",
        endpoint: "flow/sectors?sum=industry,country_id&form_id=20020001",
        title: "Sector Distribution",
        id: "sector-ddistribution",
        parentId: "first-row",
    },
    countChildren
);

getMaps("maps", "home/map");
getCharts("home/investment-tracking", "second-row", "12", "Investment Tracking (Euros)", 375);

$('.popover-graphic').popover({
    trigger: 'hover focus',
    placement: 'top'
});