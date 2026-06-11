import { ElMessage } from 'element-plus';
export function printQrCode(title: string, printContext: string) {
  try {

    let iframeId = 'printableIfame';
    let existIframe = document.querySelector('#iframeId');
    if (!existIframe) {
      let iframeObj = document.createElement('iframe');
      iframeObj.id = iframeId;
      iframeObj.style.display = 'none';
      document.body.appendChild(iframeObj);
    }
    let iframe = document.getElementById(iframeId) as HTMLIFrameElement;
    var iframeDoc = iframe.contentDocument;
    if (iframeDoc) {
      // 构建iframe的HTML内容
      iframeDoc.open();
      iframeDoc.write(`
         <!DOCTYPE html>
         <html lang="en">
           <head>
             <meta charset="UTF-8">
             <meta name="viewport" content="width=device-width, initial-scale=1.0">
             <title>${title}</title>
           </head>
           <body>
              <div style="width: 100px; height: 100px; display: flex; justify-content: center; align-items: center;">
             ${printContext}
             </div>
             <script>
               setTimeout(() => {
                 window.print();
                 document.body.removeChild(${JSON.stringify(iframeId)});
               }, 0);
             </script>
           </body>
         </html>
       `);
      iframeDoc.close();
    }
  } catch (error) {
    // 如果调用过程中出现错误，打印功能很可能不可用
    alert('当前设备不支持打印功能');
  }
}

// 处理下载逻辑
export const downloadFile = async (url: string, fileName?: string) => {
  if (!url) return;

  try {
    let response = await fetch(url);
    // 内容转变成blob地址
    let blob = await response.blob();
    // 创建隐藏的可下载链接
    let objectUrl = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    //地址
    a.href = objectUrl;
    //修改文件名
    if (fileName) a.setAttribute('download', fileName);
    // 触发点击
    document.body.appendChild(a);
    a.click();
    //移除a标签
    document.body.removeChild(a);
    window.URL.revokeObjectURL(objectUrl); // 释放掉blob对象,清理url
  } catch (e) {
    console.error("downloadFile error:", e);
  }
}

export const downloadBolb = async (blobData: Blob, fileName?: string) => {
  try {
    let url = window.URL || window.webkitURL;
    let mimeType = blobData.type || 'application/octet-stream';
    let objectUrl = url.createObjectURL(new Blob([blobData], { type: mimeType }));
    let a = document.createElement('a');
    //地址
    a.href = objectUrl;
    a.target = '_blank';
    //修改文件名
    if (fileName) a.download = fileName.split('/').pop() || fileName;
    // 触发点击
    document.body.appendChild(a);
    a.click();
    //移除a标签
    a.remove();
    url.revokeObjectURL(objectUrl); // 释放掉blob对象
  } catch (e) {
    console.error("downloadBolb error:", e);
  }
}

export const newWindow = (path: string) => {
  if (!path) return;
  let url = window.location.origin + path;
  if (process.env.NODE_ENV === 'development') {
    url = "/" + path;
  }
  window.open(url, '_blank');
}

export const openFile = (path: string) => {
  if (!path) return;
  window.open(path, '_blank');
}

export const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    ElMessage.success('复制成功');
  }).catch(err => {
    ElMessage.error('复制失败', err);
  });
}


export function base64ToBlobUrl(base64: string, mimeType: string): string {
  const byteCharacters = atob(base64);
  const byteNumbers = new Array(byteCharacters.length);
  for (let i = 0; i < byteCharacters.length; i++) {
    byteNumbers[i] = byteCharacters.charCodeAt(i);
  }
  const byteArray = new Uint8Array(byteNumbers);
  const blob = new Blob([byteArray], { type: mimeType });
  return URL.createObjectURL(blob);
}

export function base64ToBlob(base64: string, mimeType: string = "application/octet-stream"): Blob {
  const byteCharacters = atob(base64);
  const byteNumbers = new Array(byteCharacters.length);
  for (let i = 0; i < byteCharacters.length; i++) {
    byteNumbers[i] = byteCharacters.charCodeAt(i);
  }
  const byteArray = new Uint8Array(byteNumbers);
  return new Blob([byteArray], { type: mimeType });
}

// 过滤数据，过滤掉source对象中，format对象不存在的属性，以便给format对象赋值
export function filterData(format: any, source: any) {
  const result: any = {};
  Object.keys(format).forEach(key => {
    if (source.hasOwnProperty(key)) {
      result[key] = source[key];
    }
  });
  return result;
}