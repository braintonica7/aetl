import { DataProvider, fetchUtils, HttpError } from 'ra-core';
import * as apiClient from '../common/apiClient';
import { getQuestionsAnalysis } from '../common/apiClient';


const UploadFile = (field: string, data: any) => {
  const formData = new FormData();
  formData.append(field, data.rawFile);
  let finalUrl = `${apiClient.APIUrl}/upload`;
  return fetch(`${finalUrl}`, {
    method: 'POST',
    body: formData
  })
    .then((response) => response.json())
    .then((responseJson) => {
      return responseJson;
    })
    .catch((error) => {
      console.log(error);
    });
};

const fetchJson = (url, options: any = {}) => {
  if (!options.headers) {
    options.headers = new Headers({ Accept: 'application/json' });
  }
  
  // First check for JWT access token, fallback to old token
  let token = localStorage.getItem('jwt_access_token');
  if (!token) {
    token = localStorage.getItem('token');
  }
  
  if (token) {
    const authHeader = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
    options.headers.set('Authorization', authHeader);
  }
  
  return fetchUtils.fetchJson(url, options);
};

export const MyDataProvider = (
  apiUrl: string,
  httpClient = fetchJson
): DataProvider => {
  return {
    getList: (resource: any, params: any) => {
      // Handle special case for notification-preview
      if (resource === 'notification-preview') {
        const { page, perPage } = params.pagination;
        const filter = params.filter || {};
        
        const queryParams = new URLSearchParams({
          limit: perPage.toString(),
          page: page.toString()
        });
        
        if (filter.search) {
          queryParams.append('search', filter.search);
        }
        if (filter.notification_type) {
          queryParams.append('notification_type', filter.notification_type);
        }
        if (filter.priority) {
          queryParams.append('priority', filter.priority);
        }
        if (filter.segment) {
          queryParams.append('segment', filter.segment);
        }
        
        const finalUrl = `${apiUrl}/notification_preview?${queryParams}`;
        return httpClient(finalUrl).then(({ headers, json }) => {
          return {
            data: json.data || [],
            total: json.pagination?.total_eligible_users || json.data?.length || 0
          };
        });
      }

      // Handle special case for question-analysis
      if (resource === 'question-analysis') {
        return getQuestionsAnalysis(params).then((result) => {
          return {
            data: result.data,
            total: result.total
          };
        });
      }

      // Handle special case for flagged-questions
      if (resource === 'flagged-questions') {
        const { page, perPage } = params.pagination;
        const { field, order } = params.sort;
        const filter = params.filter;
        const filterString = JSON.stringify(filter);
        const finalUrl = `${apiUrl}/question/flagged?pagesize=${perPage}&page=${page}&sortby=${field}&sortorder=${order}&filter=${filterString}`;
        return httpClient(finalUrl).then(({ headers, json }) => {
          return {
            data: json.result || [],
            total: json.pagination ? json.pagination.total_records : (json.result ? json.result.length : 0)
          };
        });
      }

      // Handle special case for quiz resource
      if (resource === 'quiz') {
        const { page, perPage } = params.pagination;
        const { field, order } = params.sort;
        const filter = params.filter;
        const filterString = JSON.stringify(filter);
        const finalUrl = `${apiUrl}/quiz?pagesize=${perPage}&page=${page}&sortby=${field}&sortorder=${order}&filter=${filterString}`;
        return httpClient(finalUrl).then(({ headers, json }) => {
          return {
            data: json.result || [],
            total: json.total || 0
          };
        });
      }

      if (resource === 'user') {
        let filterInner =   params.filter;
        if(filterInner.hasOwnProperty('q')){
          filterInner.display_name = filterInner.q;
          delete filterInner.q;
          params.filter = filterInner;
        }
      }
      
      // Default behavior for other resources
      const { page, perPage } = params.pagination;
      const { field, order } = params.sort;
      const filter = params.filter;
      const filterString = JSON.stringify(filter);
      const finalUrl = `${apiUrl}/${resource}?pagesize=${perPage}&page=${page}&sortby=${field}&sortorder=${order}&filter=${filterString}`;
      return httpClient(finalUrl).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    getOne: (resource: any, params: any) => {
      const finalUrl = `${apiUrl}/${resource}/${params.id}`;
      return httpClient(finalUrl).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    getMany: (resource: any, params: any) => {
      var ids = params.ids;
      let where = '';
      if (ids.length == 1) {
        where = `mid=` + ids[0];
      } else {
        where = `mid=`;
        ids.forEach(function (id, index) {
          where += `${id},`;
        });
      }

      const finalUrl = `${apiUrl}/${resource}?${where}`;
      return httpClient(finalUrl).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    getManyReference: (resource: any, params: any) => {
      const { page, perPage } = params.pagination;
      const { field, order } = params.sort;
      const filter = params.filter;
      

      if (params.target != undefined) {
        let col = params.target + '=';
        let val = params.id;
        filter[col] = val;
      }

      const filterString = JSON.stringify(filter);
      //debugger;

      // var ids = params.ids;
      // let where = "";
      // if (ids) {
      //     if (ids.length == 1) {
      //         where = `&mid=` + ids[0];
      //     } else {
      //         where = `&mid=`;
      //         ids.forEach(function (id, index) {
      //             where += `${id},`;
      //         });
      //     }
      // }
      //const finalUrl = `${apiUrl}/${resource}?pagesize=${perPage}&page=${page}&sortby=${field}&sortorder=${order}&filter=${filterString}${where}`;
      const finalUrl = `${apiUrl}/${resource}?pagesize=${perPage}&page=${page}&sortby=${field}&sortorder=${order}&filter=${filterString}`;

      return httpClient(finalUrl).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    create: async (resource: any, params: any) => {
      let data = params.data;
      

      if (data.hasOwnProperty("quiz_detail_image")) {
        if (data.quiz_detail_image != null && data.quiz_detail_image.rawFile != null) {
          let response = await UploadFile("file", data.quiz_detail_image);
          if (response.status === 'success') {
            data.quiz_detail_image = response.result;
          } else {
            data.quiz_detail_image = '';
          }
        }
      }

      if (data.hasOwnProperty('cover_image')) {
        if (data.cover_image != null && data.cover_image.rawFile != null) {
          let response = await UploadFile('file', data.cover_image);
          if (response.status === 'success') {
            data.cover_image = response.result;
          } else {
            data.cover_image = '';
          }
        }
      }

      if (data.hasOwnProperty('question_img_url')) {
        if (
          data.question_img_url != null &&
          data.question_img_url.rawFile != null
        ) {
          let response = await UploadFile('file', data.question_img_url);
          if (response.status === 'success') {
            data.question_img_url = response.result;
          } else {
            data.question_img_url = '';
          }
        }
      }

      if (data.hasOwnProperty('file_url')) {
        if (data.file_url != null && data.file_url.rawFile != null) {
          let response = await UploadFile('file', data.file_url);
          if (response.status === 'success') {
            data.file_url = response.result;
          } else {
            data.file_url = '';
          }
        }
      }

      const finalUrl = `${apiUrl}/${resource}`;
      return httpClient(finalUrl, {
        method: 'POST',
        body: JSON.stringify(data)
      }).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    update: async (resource: any, params: any) => {
      let data = params.data;

      if (data.hasOwnProperty('question_img_url')) {
        if (
          data.question_img_url != null &&
          data.question_img_url.rawFile != null
        ) {
          let response = await UploadFile('file', data.question_img_url);
          if (response.status === 'success') {
            data.question_img_url = response.result;
          } else {
            data.question_img_url = '';
          }
        }
      }

      if (data.hasOwnProperty("quiz_detail_image")) {
        if (data.quiz_detail_image != null && data.quiz_detail_image.rawFile != null) {
          let response = await UploadFile("file", data.quiz_detail_image);
          if (response.status === 'success') {
            data.quiz_detail_image = response.result;
          } else {
            data.quiz_detail_image = '';
          }
        }
      }

      if (data.hasOwnProperty('cover_image')) {
        if (data.cover_image != null && data.cover_image.rawFile != null) {
          let response = await UploadFile('file', data.cover_image);
          if (response.status === 'success') {
            data.cover_image = response.result;
          } else {
            data.cover_image = '';
          }
        }
      }
      
      const finalUrl = `${apiUrl}/${resource}/${params.id}`;
      return httpClient(finalUrl, {
        method: 'PUT',
        body: JSON.stringify(data)
      }).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    updateMany: (resource: any, params: any) => {
      const finalUrl = `${apiUrl}/${resource}`;
      return httpClient(finalUrl).then(({ headers, json }) => {
        return {
          data: json.result,
          total: 10
        };
      });
    },
    delete: (resource: any, params: any) => {
      const finalUrl = `${apiUrl}/${resource}/${params.id}`;
      return httpClient(finalUrl, {
        method: 'DELETE'
      }).then(({ headers, json }) => {
        return {
          data: json.result,
          total: json.total
        };
      });
    },
    deleteMany: (resource: any, params: any) => {
      const finalUrl = `${apiUrl}/${resource}`;
      return httpClient(finalUrl, {
        method: 'DELETE'
      }).then(({ headers, json }) => {
        return {
          data: json.result,
          total: 10
        };
      });
    }
  };
};
