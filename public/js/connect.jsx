import { ConnectButton } from "@rainbow-me/rainbowkit";
import { useAccount, useNetwork, useSignMessage } from "wagmi";
import { SiweMessage } from "siwe";

const { __ } = wp.i18n;
const { ADMIN_URL, LOGIN_API, NONCE_API, SITE_TITLE } = wpRainbowData;

export const WPRainbowConnect = () => {
  const [state, setState] = React.useState({});
  const [{ data: accountData, loading }] = useAccount({
    fetchEns: true,
  });
  const [{ data: networkData }] = useNetwork();
  const [, signMessage] = useSignMessage();

  const [hasLoadedFirstTime, setHasLoadedFirstTime] = React.useState(false);
  const [isLoadingSecondTime, setIsLoadingSecondTime] = React.useState(false);
  const [hasLoadedSecondTime, setHasLoadedSecondTime] = React.useState(false);

  React.useEffect(() => {
    if (accountData && !loading && !hasLoadedFirstTime) {
      setHasLoadedFirstTime(true);
    }
    if (accountData && loading && hasLoadedFirstTime) {
      setIsLoadingSecondTime(true);
    }
    if (
      accountData &&
      !loading &&
      hasLoadedFirstTime &&
      isLoadingSecondTime &&
      !hasLoadedSecondTime
    ) {
      setIsLoadingSecondTime(false);
      setHasLoadedSecondTime(true);
    }
  }, [
    accountData,
    loading,
    hasLoadedFirstTime,
    isLoadingSecondTime,
    hasLoadedSecondTime,
  ]);

  const signIn = React.useCallback(async () => {
    try {
      const address = accountData?.address;
      const chainId = networkData?.chain?.id;
      if (!address || !chainId) return;

      setState((x) => ({ ...x, error: undefined, loading: true }));
      const nonceRes = await fetch(NONCE_API);
      const nonce = await nonceRes.json();
      const siwePayload = {
        address,
        chainId,
        domain: window.location.host,
        issuedAt: new Date().toISOString(),
        nonce,
        statement: `Log In with Ethereum to ${SITE_TITLE}`,
        uri: window.location.origin,
        version: "1",
      };
      const message = new SiweMessage(siwePayload);
      const signRes = await signMessage({
        message: message.prepareMessage(),
      });
      if (signRes.error) throw signRes.error;
      const verifyRes = await fetch(LOGIN_API, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          address,
          displayName: accountData?.ens?.name ?? address,
          nonce,
          signature: signRes.data,
          siwePayload,
        }),
      });
      if (!verifyRes.ok) throw new Error("Error verifying message");
      setState((x) => ({ ...x, address, loading: false }));
      document.getElementById("loginform").classList.add("logged-in");
      window.location = ADMIN_URL;
    } catch (error) {
      console.log(error);
      setState((x) => ({ ...x, error, loading: false }));
    }
  }, [accountData, networkData, loading]);

  const [triggeredLogin, setTriggeredLogin] = React.useState(false);
  React.useEffect(() => {
    if (accountData && !triggeredLogin && hasLoadedSecondTime) {
      signIn();
      setTriggeredLogin(true);
    } else if (!accountData && state.address) {
      setState({});
      setTriggeredLogin(false);
      document.getElementById("loginform").classList.remove("logged-in");
    }
  }, [accountData, state.address, loading, hasLoadedSecondTime]);

  const siteLoginText = (
    <p
      className="wp-rainbow help-text"
      style={{
        fontSize: "12px",
        fontStyle: "italic",
        marginBottom: "4px",
        marginTop: "4px",
        textAlign: "center",
      }}
    >
      {__("- OR USE SITE LOGIN -", "wp-rainbow")}
    </p>
  );

  return (
    <>
      <ConnectButton.Custom>
        {({ account, openAccountModal, openConnectModal }) => {
          if (state.error) {
            return (
              <React.Fragment>
                <button
                  className="button button-secondary button-hero"
                  onClick={() => window.location.reload()}
                  style={{ width: "100%" }}
                  type="button"
                >
                  {__("Log In Error, Click to Refresh", "wp-rainbow")}
                </button>
                {siteLoginText}
              </React.Fragment>
            );
          }
          if (account) {
            return (
              <React.Fragment>
                <button
                  className="button button-secondary button-hero"
                  onClick={state.address ? openAccountModal : signIn}
                  type="button"
                  disabled={state.loading}
                  style={{ width: "100%" }}
                >
                  {state.address
                    ? `${__("Logged In as ")} ${account.displayName}`
                    : state.loading
                    ? __("Check Wallet to Sign Message")
                    : __("Continue Log In with Ethereum")}
                </button>
                {siteLoginText}
              </React.Fragment>
            );
          }
          return (
            <React.Fragment>
              <button
                className="button button-secondary button-hero"
                onClick={openConnectModal}
                style={{ width: "100%" }}
                type="button"
              >
                {__("Log In with Ethereum", "wp-rainbow")}
              </button>
              {siteLoginText}
            </React.Fragment>
          );
        }}
      </ConnectButton.Custom>
    </>
  );
};

export default WPRainbowConnect;
