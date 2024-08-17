(() => {
  let wasInitialized = false;

  let fileInput;
  let fileInputButton;
  let fileInputName;

  let spinner;

  let result;

  /**
   * Initialize the functionality.
   */
  const initialize = () => {
    if (wasInitialized) {
      return;
    }
    wasInitialized = true;

    fileInput = document.querySelector('.file-input');
    fileInput.addEventListener('change', () => {
      spinner.classList.remove('display-none');
      fileInputName.innerHTML = fileInput.value;
      uploadFile();
      fileInput.value = '';
    });

    fileInputButton = document.querySelector('.file-input-button');
    fileInputButton.addEventListener('click', () => {
      fileInput.focus();
      return false;
    });

    spinner = document.querySelector('.spinner');
    spinner.classList.add('display-none');

    fileInputName = document.querySelector('.file-input-name');
    fileInputName.innerText = '';

    result = document.querySelector('.result');
    result.classList.add('display-none');
    result.innerText = '';
  };

  /**
   * Get the filename from the Content-Disposition header.
   * @param {string} disposition Content-Disposition header.
   * @returns {string} Filename.
   */
  const getDownloadFilename = (disposition) => {
    let filename = `EscapeRoom-${(new Date()).valueOf()}.h5p`; // Fallback

    if (disposition?.includes("attachment")) {
      const matches = disposition.match(
        /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/
      );
      if (matches?.[1]) {
        filename = matches[1].replace(/['"]/g, '');
      }
    }

    return filename;
  };

  /**
   * Offer the file for download.
   * @param {} response Response from server.
   * @param {string} filename Filename to use.
   */
  const offerFileForDownload = (response, filename) => {
    const blob = new Blob([response], { type: "application/zip" });

    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);

    link.click();

    document.body.removeChild(link);

    // Revoke the object URL to free up memory
    window.URL.revokeObjectURL(link.href);
  };

  /**
   * Done handler.
   *
   * @param {string} message Message to display.
   * @param {boolean} [isError] If true, message is an error.
   */
  const done = (message, isError = false) => {
    fileInputButton.removeAttribute("disabled");

    spinner.classList.add("display-none");

    result.innerText = message;
    result.classList.toggle('error', isError);
    result.classList.toggle('display-none', !message);
  };

  /**
   * Handle the file upload.
   */
  const uploadFile = async () => {
    try {
      fileInputButton.setAttribute("disabled", "disabled");
      result.classList.remove("error");
      result.classList.add("display-none");
      result.innerText = "";

      const formData = new FormData();
      formData.append("file", fileInput.files[0]);

      const response = await fetch("./upload.php", {
        method: "POST",
        body: formData,
      });

      if (response.ok) {
        const disposition = response.headers.get("Content-Disposition");
        const filename = getDownloadFilename(disposition);

        const blob = await response.blob();
        offerFileForDownload(blob, filename);

        done("File converted successfully!");
      }
      else {
        done(await response.text(), true);
      }
    }
    catch (error) {
      done(error,message, true);
    }
  };

  // Start when ready
  if (["complete", "interactive"].includes(document.readyState)) {
    initialize();
  }
  else {
    document.addEventListener("readystatechange", () => {
      if (!wasInitialized) {
        initialize();
      }
    });
  }
})();
