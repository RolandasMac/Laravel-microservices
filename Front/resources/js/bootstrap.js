import axios from "axios";

window.axios = axios;

// Nustatykite bazinį URL
window.axios.defaults.baseURL = "http://localhost:8001/getaway/"; // Pakeiskite į savo API URL

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

window.axios.defaults.withCredentials = true;

// Patikrinkite, ar yra autentifikavimo tokenas localStorage
// const authToken = localStorage.getItem("authToken");
const authToken = "7|IQOU4ZF64QKalz9z6Pp5j8N2W8pIx4ZgQnGsskS5b2206fa8";
if (authToken) {
    window.axios.defaults.headers.common[
        "Authorization"
    ] = `Bearer ${authToken}`;
}
