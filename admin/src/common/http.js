export const setHeaders = (headers) => {
    if (!headers) headers = new Headers();
    
    // First check for JWT access token
    let jwtToken = localStorage.getItem('jwt_access_token');
    if (jwtToken) {
        headers.append("Authorization", "Bearer " + jwtToken);
    } else {
        // Fallback to old token system for backward compatibility
        let token = localStorage.getItem('token');
        if (token) {
            headers.append("Authorization", "Bearer " + token);
        }
    }
    
    headers.append("Content-Type", "application/json");
    return headers;
}

export const post = (url, model = null, headers = null) => {
    headers = setHeaders(headers);
    return fetch(url, {
        method: "POST",
        body: JSON.stringify(model),
        headers
    }).then(r => {
        if (r.status == 409) {
            return r.json();
        }
        if (!r.ok) {
            throw Error(r.statusText);
        }
        return r.json();
    });
};

export const patch = (url, model = null, headers = null) => {
    headers = setHeaders(headers);
    return fetch(url, {
        method: "PATCH",
        body: JSON.stringify(model),
        headers
    }).then(r => {
        if (r.status == 409) {
            return r.json();
        }
        if (!r.ok) {
            throw Error(r.statusText);
        }
        return r.json();
    });
};

export const get = (url, headers = null) => {
    headers = setHeaders(headers);
    return fetch(url, {
        method: "GET",
        headers
    }).then(r => {
        if (!r.ok) {
            throw Error(r.statusText);
        }
        
        
        return r.json();
    });
};

export const put = (url, model, headers = null) => {
    headers = setHeaders(headers);
    return fetch(url, {
        method: "PUT",
        body: JSON.stringify(model),
        headers
    }).then(r => {
        if (!r.ok) {
            throw Error(r.statusText);
        }
        return r.json();
    });
};
// delete is reserved word so del
export const del = (url, headers) => {
    headers = setHeaders(headers);
    return fetch(url, {
        method: "DELETE",
        headers
    }).then(r => {
        if (!r.ok) {
            throw Error(r.statusText);
        } else if (r.status == 204) {
            return r.statusText;
        }
        return r.json();
    });
};

