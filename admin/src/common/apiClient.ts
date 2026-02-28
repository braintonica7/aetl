import * as http from "./http";
import { APIUrl, AI_API_URL } from "./apiConfig";

// Export the API URLs from the centralized config
export { APIUrl, AI_API_URL };

export const innerUploadFile = (field, data) => {
    const formData = new FormData();
    formData.append(field, data.rawFile);
    return fetch(`${APIUrl}uploadFile`, {
        method: 'POST',
        body: formData
    }).then((response) => response.json())
        .then((responseJson) => { 
            return responseJson;
        })
        .catch((error) => {
            console.log(error);
        });
}

export const getBaseUrl = (url) => {
    if (url.indexOf("?") != -1) {
        url = url.substring(0, url.indexOf("?"));
    }
    return url;
}

export const UploadFile = async (field, data) => {
    if (data && data.rawFile) {
        let response = await innerUploadFile(field, data);
        if (response.files && response.files.length > 0) {
            return getBaseUrl(response.files[0].url);
        } else {
            return data;
        }
    }
    return data;
}


export const formatDate = (date) => {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2)
        month = '0' + month;
    if (day.length < 2)
        day = '0' + day;
    return [year, month, day].join('-');
}

export const loginUser = (user) => {
    let api = APIUrl + "user/login";
    return http.post(api, user).then(response => {
        return response;
    });
}

export const adminLogin = (credentials: { username: string; password: string }) => {
    let api = APIUrl + "login/admin_login";
    return http.post(api, credentials).then(response => {
        return response;
    });
}

// New JWT Authentication functions
export const adminJWTLogin = (credentials: { username: string; password: string; device_info?: string }) => {
    let api = APIUrl + "login/jwt_admin_login";
    return http.post(api, credentials).then(response => {
        return response;
    });
}

export const adminJWTRefresh = (data: { refresh_token: string }) => {
    let api = APIUrl + "login/jwt_refresh";
    return http.post(api, data).then(response => {
        return response;
    });
}

export const adminJWTLogout = () => {
    let api = APIUrl + "login/jwt_logout";
    return http.post(api, {}).then(response => {
        return response;
    });
}

export const adminJWTLogoutAll = () => {
    let api = APIUrl + "login/jwt_logout_all";
    return http.post(api, {}).then(response => {
        return response;
    });
}


export const getQuestionsForQuiz = async (quizId: number) => {
    let api = `${APIUrl}quiz/get_questions_for_quiz?quiz_id=${quizId}`;
    return http.get(api).then(response => {
        return response;
    }).catch(error => {
        console.error('Get Questions for Quiz API Error:', error);
        throw error;
    });
}

export const getRecord = async (resource: string, id: number) => {
    let api = `${APIUrl}${resource}/${id}`;
    return http.get(api).then(response => {
        return response;
    }).catch(error => {
        console.error(`Get ${resource} Record API Error:`, error);
        throw error;
    });
}

export const deleteRecord = async (path, id) => {
    let api = APIUrl + path + "/" + id;
    return await http.del(api).then(response => {
        return response;
    });
}

export const addEntiyRecord = (resource, data) => {
    let api = APIUrl + resource;
    return http.post(api, data).then(response => {
        return response;
    });
}

export const updateEntiyRecord = (resource, data) => {
    let api = APIUrl + resource + "/" + data.id;
    return http.put(api, data).then(response => {
        return response;
    });
}

export const ChangePassword = (data) => {
    let api = APIUrl + "users/update_password";
    return http.post(api, data).then(response => {
        return response;
    });
}

export const innerUploadImportFile = (field, data) => {
    const formData = new FormData();
    formData.append(field, data.rawFile);
    return fetch(`${APIUrl}fileimport`, {
        method: 'POST',
        body: formData
    }).then((response) => response.json())
        .then((responseJson) => {
            return responseJson;
        })
        .catch((error) => {
            console.log(error);
        });
}

export const UploadImportFile = async (field, data) => {
    if (data && data.rawFile) {
        let response = await innerUploadImportFile(field, data);
        return response;
    }
    return data;
}


export const fetchSolution = (image_url, suggestion = "") => {
  let api = `${AI_API_URL}solution_from_image_by_url`;
  let data:any ={
    image_url: image_url,
    suggestion: suggestion

  }
  return http.post(api, data).then((response) => {
    return response;
  });
};

export const updateQuestionSolution = (id, solution = "") => {
  let api = `${APIUrl}question/solution/${id}`;
  let data:any ={
    solution: solution,
    id: id
  }
  return http.post(api, data).then((response) => {
    return response;
  });
};

export const updateWiziQuestionSolution = (id, solution = "") => {
  let api = `${APIUrl}wizi_question/solution/${id}`;
  let data:any ={
    solution: solution,
    id: id
  }
  return http.post(api, data).then((response) => {
    return response;
  });
};

// Dashboard APIs
export const getQuestionSummary = (questionType: string = 'regular') => {
  let api = `${APIUrl}dashboard/question_summary?question_type=${questionType}`;
  return http.get(api).then((response) => {
    return response;
  });
};

export const getQuestionsBySubject = (subjectId: string) => {
  let api = `${APIUrl}dashboard/questions_by_subject/${subjectId}`;
  return http.get(api).then((response) => {
    return response;
  });
};

export const getAllSubjects = () => {
  let api = `${APIUrl}subject`;
  return http.get(api).then((response) => {
    return response;
  });
};

// Question Analysis API
export const getQuestionsAnalysis = (params: any) => {
  let api = `${APIUrl}question/analysis`;
  const queryParams = new URLSearchParams();
  
  if (params.pagination) {
    queryParams.append('page', String(params.pagination.page));
    queryParams.append('pagesize', String(params.pagination.perPage));
  }
  
  if (params.sort) {
    queryParams.append('sortby', params.sort.field);
    queryParams.append('sortorder', params.sort.order);
  }
  
  if (params.filter && Object.keys(params.filter).length > 0) {
    queryParams.append('filter', JSON.stringify(params.filter));
  }
  
  const queryString = queryParams.toString();
  if (queryString) {
    api += `?${queryString}`;
  }
  
  return http.get(api).then((response) => {
    return {
      data: response.result || [],
      total: response.total || 0
    };
  });
};

// User Quiz Statistics API
export const getUserQuizStatistics = (params: any) => {
  let api = `${APIUrl}quiz/users_quiz_statistics`;
  const queryParams = new URLSearchParams();
  
  if (params.pagination) {
    queryParams.append('page', String(params.pagination.page));
    queryParams.append('limit', String(params.pagination.perPage));
  }
  
  if (params.sort) {
    queryParams.append('sort_by', params.sort.field);
    queryParams.append('sort_order', params.sort.order);
  }
  
  if (params.filter && Object.keys(params.filter).length > 0) {
    Object.keys(params.filter).forEach(key => {
      if (params.filter[key] !== '' && params.filter[key] !== null && params.filter[key] !== undefined) {
        queryParams.append(key, String(params.filter[key]));
      }
    });
  }
  
  const queryString = queryParams.toString();
  if (queryString) {
    api += `?${queryString}`;
  }
  
  return http.get(api).then((response) => {
    return {
      data: response.result || {},
      users: response.result?.users || [],
      pagination: response.result?.pagination || {},
      total: response.result?.pagination?.total_users || 0
    };
  });
};

// WiZi Quiz Question Management APIs
export const getWiziQuizQuestions = (wiziQuizId: number) => {
  const api = `${APIUrl}wizi_quiz_question?wizi_quiz_id=${wiziQuizId}`;
  return http.get(api).then((response) => {
    return {
      success: response.status === "success",
      data: response.result || []
    };
  });
};

export const getAvailableWiziQuestions = (wiziQuizId: number, filters: any, pagesize: number = 100) => {
  const queryParams = new URLSearchParams({
    wizi_quiz_id: wiziQuizId.toString(),
    pagesize: pagesize.toString(),
    ...Object.fromEntries(
      Object.entries(filters).filter(([_, v]) => v !== '' && v !== null)
    )
  });
  
  const api = `${APIUrl}wizi_quiz_question/available?${queryParams}`;
  return http.get(api).then((response) => {
    return {
      success: response.status === "success",
      data: response.result || []
    };
  });
};

export const bulkAddWiziQuizQuestions = (data: {
  wizi_quiz_id: number;
  question_ids: number[];
  marks: number;
  negative_marks: number;
}) => {
  const api = `${APIUrl}wizi_quiz_question/bulk_add`;
  return http.post(api, data).then((response) => {
    return {
      success: response.status === "success",
      data: response.result,
      message: response.message
    };
  });
};

export const deleteWiziQuizQuestion = (id: number) => {
  const api = `${APIUrl}wizi_quiz_question/${id}`;
  return http.del(api).then((response) => {
    return {
      success: response.status === "success",
      message: response.message
    };
  });
};

export const updateWiziQuizQuestionMarks = (id: number, data: {
  question_order?: number;
  marks: number;
  negative_marks: number;
}) => {
  const api = `${APIUrl}wizi_quiz_question/${id}`;
  return http.put(api, data).then((response) => {
    return {
      success: response.status === "success",
      message: response.message
    };
  });
};

// ============================================================================
// Notification Preview API Methods
// ============================================================================

/**
 * Send notification to a single user
 * Uses existing notifications/send endpoint
 */
export const sendNotification = async (userId: number, notificationType: string, title?: string, body?: string) => {
  const api = `${APIUrl}notifications/send`;
  return http.post(api, {
    title: title || 'Notification',
    body: body || 'You have a new notification',
    type: notificationType,
    target_type: 'specific_user',
    target_user_id: userId
  }).then((response) => {
    if (!response.success) {
      throw new Error(response.message || 'Failed to send notification');
    }
    return response.data;
  });
};

/**
 * Send notifications to multiple users
 * Uses existing notifications/send endpoint in a loop
 */
export const sendBulkNotifications = async (users: Array<{ user_id: number; notification_type: string; title?: string; body?: string }>) => {
  const results = {
    total: users.length,
    sent: 0,
    failed: 0,
    details: [] as Array<{ user_id: number; success: boolean; error?: string }>
  };

  for (const user of users) {
    try {
      await sendNotification(user.user_id, user.notification_type, user.title, user.body);
      results.sent++;
      results.details.push({ user_id: user.user_id, success: true });
    } catch (error: any) {
      results.failed++;
      results.details.push({ user_id: user.user_id, success: false, error: error.message });
    }
  }

  return results;
};

/**
 * Get notification preview for a specific user
 */
export const getNotificationPreview = async (userId: number) => {
  const api = `${APIUrl}notification_preview/${userId}`;
  return http.get(api).then((response) => {
    if (!response.success) {
      throw new Error(response.message || 'Failed to fetch notification preview');
    }
    return response.data;
  });
};

/**
 * Send batch notifications to multiple users
 * @param params - Batch send parameters
 */
export const sendBatchNotifications = async (params: {
  user_ids?: number[];
  max_users?: number;
  priority_filter?: string;
  segment_filter?: string;
  dry_run?: boolean;
}) => {
  const api = `${APIUrl}notification_preview/send_batch`;
  return http.post(api, params).then((response) => {
    if (!response.success) {
      throw new Error(response.message || 'Failed to send batch notifications');
    }
    return response.data;
  });
};

/**
 * Get batch send history
 * @param limit - Number of history records to fetch
 */
export const getBatchSendHistory = async (limit: number = 20) => {
  const api = `${APIUrl}notification_preview/batch_history?limit=${limit}`;
  return http.get(api).then((response) => {
    return {
      success: response.status === "success",
      data: response.data || []
    };
  });
};

/**
 * Get batch send statistics
 */
export const getBatchSendStats = async () => {
  const api = `${APIUrl}notification_preview/batch_stats`;
  return http.get(api).then((response) => {
    return {
      success: response.status === "success",
      data: response.data || {}
    };
  });
};

// ============================================================================
// Notification Context API Methods
// ============================================================================

/**
 * Build notification contexts (batch)
 * @param {Object} params - Build parameters
 * @param {number} params.batch_size - Number of users to process
 * @param {boolean} params.stale_only - Only process stale contexts
 * @param {number[]} params.user_ids - Specific user IDs to process
 * @returns {Promise}
 */
export const buildNotificationContexts = (params: {
  batch_size?: number;
  stale_only?: boolean;
  user_ids?: number[];
} = {}) => {
  const api = `${APIUrl}notification-context/build`;
  return http.post(api, params);
};

/**
 * Build single user context
 * @param {number} userId - User ID
 * @returns {Promise}
 */
export const buildUserContext = (userId: number) => {
  const api = `${APIUrl}notification-context/build-user`;
  return http.post(api, { user_id: userId });
};

/**
 * Get context statistics
 * @returns {Promise}
 */
export const getContextStats = () => {
  const api = `${APIUrl}notification-context/stats`;
  return http.get(api);
};

/**
 * Reset daily counters
 * @returns {Promise}
 */
export const resetDailyCounters = () => {
  const api = `${APIUrl}notification-context/reset-counters`;
  return http.post(api, {});
};

/**
 * Get current build status
 * @returns {Promise}
 */
export const getBuildStatus = () => {
  const api = `${APIUrl}notification-context/build-status`;
  return http.get(api);
};

/**
 * Get recent build history
 * @param {number} limit - Number of builds to return
 * @returns {Promise}
 */
export const getBuildHistory = (limit: number = 20) => {
  const api = `${APIUrl}notification-context/recent-builds?limit=${limit}`;
  return http.get(api);
};

/**
 * Get eligible users preview by notification type
 * @param {string} notificationType - Type of notification
 * @param {number} limit - Preview limit
 * @returns {Promise}
 */
export const getEligibleUsersPreview = (notificationType: string, limit: number = 100) => {
  const api = `${APIUrl}notification-context/eligible-users?type=${notificationType}&limit=${limit}`;
  return http.get(api);
};
