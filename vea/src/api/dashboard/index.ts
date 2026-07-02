import request from "@/utils/request";

const BaseUrl = "/api/v1/dashboard";
const DashboardAPI = {
  index(postData: any) {
    return request({
      url: `${BaseUrl}/index`,
      method: "post",
      data: postData
    });
  },
  systemInfo() {
    return request({
      url: `${BaseUrl}/systemInfo`,
      method: "get"
    });
  }

}

export default DashboardAPI;
