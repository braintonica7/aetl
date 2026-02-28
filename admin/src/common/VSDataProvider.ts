import { stringify } from "query-string";
import { fetchUtils, DataProvider, HttpError } from "ra-core";
import * as apiClient from "../common/apiClient";
import moment from "moment";

import uuid from "react-uuid";

/**
 * Maps react-admin queries to a json-server powered REST API
 *
 * @see https://github.com/typicode/json-server
 *
 * @example
 *
 * getList          => GET http://my.api.url/posts?_sort=title&_order=ASC&_start=0&_end=24
 * getOne           => GET http://my.api.url/posts/123
 * getManyReference => GET http://my.api.url/posts?author_id=345
 * getMany          => GET http://my.api.url/posts/123, GET http://my.api.url/posts/456, GET http://my.api.url/posts/789
 * create           => POST http://my.api.url/posts/123
 * update           => PUT http://my.api.url/posts/123
 * updateMany       => PUT http://my.api.url/posts/123, PUT http://my.api.url/posts/456, PUT http://my.api.url/posts/789
 * delete           => DELETE http://my.api.url/posts/123
 *
 * @example
 *
 * import React from 'react';
 * import { Admin, Resource } from 'react-admin';
 * import jsonServerProvider from 'ra-data-json-server';
 *
 * import { PostList } from './posts';
 *
 * const App = () => (
 *     <Admin dataProvider={jsonServerProvider('http://jsonplaceholder.typicode.com')}>
 *         <Resource name="posts" list={PostList} />
 *     </Admin>
 * );
 *
 * export default App;
 */
const padDate = (data, field) => {
  if (data[field]) {
    if (data[field].length == 10) {
      data[field] = data[field] + "T00:00:00.000Z";
    }
  }
  return data;
}
const setProperDates = (data) => {
    
  return data;

  /** ================================================================ */
}

const UploadFile = (field: string, data: any) => {
  const formData = new FormData();
  formData.append(field, data.rawFile);
  let APIUrl = apiClient.APIUrl; //"http://localhost:8086/";
  return fetch(`${APIUrl}uploadFile`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((responseJson) => {
      return responseJson;
    })
    .catch((error) => {
      console.log(error);
    });
};
const getBaseUrl = (url: string): string => {
  if (url.indexOf("?") != -1) {
    url = url.substring(0, url.indexOf("?"));
  }
  return url;
};

export const VSfetchJson = (url: any, options: fetchUtils.Options = {}) => {

  const requestHeaders = fetchUtils.createHeadersFromOptions(options);
  const headers = new Headers({
    Accept: "application/json",
    "Content-Type": "application/json",
    Authorization: "Bearer " + localStorage.getItem("token")
  }); 

  return fetch(url, { ...options, headers: headers })
    .then((response) =>
      response.text().then((text) => ({
        status: response.status,
        statusText: response.statusText,
        headers: response.headers,
        body: text,
      }))
    )
    .then(({ status, statusText, headers, body }) => {
      let json;
      try {
        json = JSON.parse(body);
      } catch (e) {
        // not json, no big deal
      }
      if (status == 409) {
        return Promise.reject(
          new HttpError(
            (json && json.error && json.error.message) || statusText,
            status,
            json
          )
        );
      }

      if (status == 422) {
        let message = "**VALIDATION FAILED** ";
        if (json.error && json.error.details && json.error.details.length > 0) {
          json.error.details.forEach((element: any) => {
            let f = element;
            let mf = f.path + " " + f.message;
            message = message + " , " + mf;
          });
        }
        return Promise.reject(new HttpError(message, status, json));
      }

      if (status < 200 || status >= 300) {
        return Promise.reject(
          new HttpError((json && json.message) || statusText, status, json)
        );
      }
      return Promise.resolve({ status, headers, body, json });
    });
};

export default (apiUrl: String, httpClient = VSfetchJson): DataProvider => ({
  getList: (resource, params) => {
    const { page, perPage } = params.pagination || { page: 1, perPage: 10 };
    const { field, order } = params.sort || { field: 'id', order: 'ASC' };
    const filter = params.filter;
    const query = {
      ...fetchUtils.flattenObject(params.filter),
      _sort: field,
      _order: order,
      _start: (page - 1) * perPage,
      _end: page * perPage,
    };

    var keys = Object.keys(filter);
    let where = "";
    keys.forEach(function (key) {
      var val: string = filter[key];
      var otype = typeof val;

      // console.log(otype);
      if (otype == "string") {
        if (val.startsWith("nin~") || val.startsWith("inq~")) {
          var arr = val.split("~");
          var opr = arr[0];
          var opts = arr[1].split(",");
          opts.forEach((item) => {
            if (item != "") {
              //where += `&filter[where][${key}][${opr}]=${item}`;
              where += `&filter[where][${key}][${opr}]=${item.toString().replace(" ", "_")}`;
            }
          });
        } else {
          var item = filter[key];
          var keyar = key.split("~");
          if (keyar.length > 1) {
            if (keyar[1] == "like") {
              var fld = keyar[0];
              var opr = keyar[1];
              if (item != "")
                //where += `&filter[where][${fld}][${opr}]=%${item}%`;
                where += `&filter[where][${fld}][${opr}]=%${item.toString().replace(" ", "_")}%`;

            }
            if (keyar[1] == "lte") {
              var fld = keyar[0];
              var opr = keyar[1];
              if (item != "")
                //where += `&filter[where][${fld}][${opr}]=%${item}%`;
                where += `&filter[where][and][0][${fld}][${opr}]=%${item.toString().replace(" ", "_")}%`;
            }
            if (keyar[1] == "gte") {
              var fld = keyar[0];
              var opr = keyar[1];
              if (item != "")
                //where += `&filter[where][${fld}][${opr}]=%${item}%`;
                where += `&filter[where][and][1][${fld}][${opr}]=%${item.toString().replace(" ", "_")}%`;
            }

          } else {
            if (item != "") {
              //where += `&filter[where][${key}]=${item}`;
              where += `&filter[where][${key}]=${item.toString().replace(" ", "_")}`;
            }
          }
        }
      } else if (otype == "number") {
        where += `&filter[where][${key}]=${val}`;
      }
    });
    const loopbackquery = `filter[offset]=${(page - 1) * perPage
      }&filter[limit]=${perPage}&filter[order]=${field} ${order}${where}`;
    const url = `${apiUrl}/${resource}?${loopbackquery}`;

    return httpClient(url).then(({ headers, json }) => {

      if (!headers.has("x-total-count")) {
        throw new Error(
          "The X-Total-Count header is missing in the HTTP Response. The jsonServer Data Provider expects responses for lists of resources to contain this header with the total number of results to build the pagination. If you are using CORS, did you declare X-Total-Count in the Access-Control-Expose-Headers header?"
        );
      }
      let count: string = "10";
      count = headers.get("x-total-count")!.split("/").pop()!;
      return {
        data: json,
        total: parseInt(count, 10),
      };
    });
  },

  getOne: (resource, params) =>
    httpClient(`${apiUrl}/${resource}/${params.id}`).then(({ json }) => ({
      data: json,
    })),

  getMany: (resource, params) => {
    /* const query = {
            id: params.ids,
        };
        const url = `${apiUrl}/${resource}?${stringify(query)}`; */

    var ids = params.ids;
    let where = "";
    if (ids.length == 1) {
      where = `filter[where][id]=` + ids[0];
    } else {
      ids.forEach(function (id, index) {
        where += `&filter[where][or][${index}][id]=${id}`;
      });
    }
    const url = `${apiUrl}/${resource}?${where}`;

    return httpClient(url).then(({ json }) => ({ data: json }));
  },

  getManyReference: (resource, params) => {
    const { page, perPage } = params.pagination || { page: 1, perPage: 10 };
    const { field, order } = params.sort || { field: 'id', order: 'ASC' };
    const filter = params.filter;
    const query = {
      ...fetchUtils.flattenObject(params.filter),
      [params.target]: params.id,
      _sort: field,
      _order: order,
      _start: (page - 1) * perPage,
      _end: page * perPage,
    };
    var keys = Object.keys(filter);
    let where = "";
    keys.forEach(function (key) {
      where += `&filter[where][${key}]=` + filter[key];
    });
    if (params.target != undefined) {
      let col = params.target;
      let val = params.id;
      where += `&filter[where][${col}]=${val}`;
    }

    const loopbackquery = `filter[offset]=${(page - 1) * perPage
      }&filter[limit]=${perPage}&filter[order]=${field} ${order}${where}`;
    const url = `${apiUrl}/${resource}?${loopbackquery}`;

    //const url = `${apiUrl}/${resource}?${stringify(query)}`;

    return httpClient(url).then(({ headers, json }) => {

      if (!headers.has("x-total-count")) {
        throw new Error(
          "The X-Total-Count header is missing in the HTTP Response. The jsonServer Data Provider expects responses for lists of resources to contain this header with the total number of results to build the pagination. If you are using CORS, did you declare X-Total-Count in the Access-Control-Expose-Headers header?"
        );
      }
      let count: string = "10";
      count = headers.get("x-total-count")!.split("/").pop()!;
      return {
        data: json,
        total: parseInt(count, 10),
      };
    });
  },

  update: async (resource, params) => {
    let data = params.data;
    
    var keys = Object.keys(data);
    keys.forEach((item) => {
      if (data[item] == null) {
        delete data[item];
      }
    });

    return httpClient(`${apiUrl}/${resource}/${params.id}`, {
      method: "PUT",
      body: JSON.stringify(params.data),
    }).then(({ json }) => ({ data: json }));
  },

  // json-server doesn't handle filters on UPDATE route, so we fallback to calling UPDATE n times instead
  updateMany: (resource, params) =>
    Promise.all(
      params.ids.map((id) =>
        httpClient(`${apiUrl}/${resource}/${id}`, {
          method: "PUT",
          body: JSON.stringify(params.data),
        })
      )
    ).then((responses) => ({ data: responses.map(({ json }) => json.id) })),

  create: async (resource, params) => {
    let data = params.data;
    data = setProperDates(data);

    var keys = Object.keys(data);
    keys.forEach((item) => {
      if (data[item] == null) {
        delete data[item];
      }
    });

    return httpClient(`${apiUrl}/${resource}`, {
      method: "POST",
      body: JSON.stringify(data),
    }).then(({ json }) => ({
      data: { ...json }
    }));
  },
  delete: (resource, params) =>
    httpClient(`${apiUrl}/${resource}/${params.id}`, {
      method: "DELETE",
    }).then(({ json }) => ({ data: json })),

  // json-server doesn't handle filters on DELETE route, so we fallback to calling DELETE n times instead
  deleteMany: (resource, params) =>
    Promise.all(
      params.ids.map((id) =>
        httpClient(`${apiUrl}/${resource}/${id}`, {
          method: "DELETE",
        })
      )
    ).then((responses) => ({ data: responses.map(({ json }) => json.id) })),
});
