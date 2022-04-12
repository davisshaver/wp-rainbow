export const addErrorMessage = (errorMessage) => {
  if (document.getElementById("login_error")) {
    document.getElementById("login_error").remove();
  }
  const loginError = document.createElement("div");
  loginError.id = "login_error";
  loginError.innerHTML = `<strong>Error</strong>: ${errorMessage}`;
  document
    .getElementById("login")
    .insertBefore(loginError, document.getElementById("loginform"));
};

export default addErrorMessage;
