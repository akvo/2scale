var currencyFormatter = require("currency-formatter");
export const gradients = ["purple", "peach", "blue", "morpheus-den"];

export const genCharArray = (charA, charZ) => {
    var a = [],
        i = charA.charCodeAt(0),
        j = charZ.charCodeAt(0);
    for (; i <= j; ++i) {
        a.push(String.fromCharCode(i));
    }
    return a;
};

export const formatNumber = (x) => {
    return currencyFormatter.format(x, {
        decimal: ".",
        thousand: ",",
        precision: 0,
        format: "%v",
    });
};

export const flatDeep = (arr, d = 1) => {
    return d > 0
        ? arr.reduce(
              (acc, val) =>
                  acc.concat(Array.isArray(val) ? flatDeep(val, d - 1) : val),
              []
          )
        : arr.slice();
};

export const flatten = (arr) => {
    return arr
        ? arr.reduce(
              (result, item) => [
                  ...result,
                  {
                      id: item.id,
                      name: item.name,
                      parent_id: item.parent_id,
                      childrens: item.childrens,
                  },
                  ...flatten(item.childrens),
              ],
              []
          )
        : [];
};

export const flattenChildren = (arr) => {
    return arr
        ? arr.reduce(
              (result, item) => [
                  ...result,
                  {
                      id: item.id,
                      name: item.name,
                      parent_id: item.parent_id,
                      children: item.childrens,
                  },
                  ...flatten(item.children),
              ],
              []
          )
        : [];
};

export const parentDeep = (id, data) => {
    let parent = data.find((x) => x.id === id);
    if (parent.parent_id !== null) {
        return parentDeep(parent.parent_id, data);
    }
    return parent;
};

export const toTitleCase = (str) => {
    return str.replace(/\w\S*/g, function (txt) {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
};

export const titleCase = (str) => {
    str = str.toLowerCase().split("-");
    for (var i = 0; i < str.length; i++) {
        str[i] = str[i].charAt(0).toUpperCase() + str[i].slice(1);
    }
    return str.join(" ");
};

export const scrollWindow = (x) => {
    window.scrollBy({
        top: (window.innerHeight - 100) / x,
        behavior: "smooth",
    });
};
