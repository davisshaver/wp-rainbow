import "@rainbow-me/rainbowkit/styles.css";

import {
  RainbowKitProvider,
  getDefaultWallets,
  connectorsForWallets,
} from "@rainbow-me/rainbowkit";
import { WagmiProvider, chain } from "wagmi";
import { providers } from "ethers";

import { WPRainbowConnect } from "./connect";

const { INFURA_ID, SITE_TITLE } = wpRainbowData;

const provider = ({ chainId }) =>
  new providers.InfuraProvider(chainId, INFURA_ID);

const chains = [{ ...chain.mainnet, name: "Ethereum" }];

const wallets = getDefaultWallets({
  chains,
  infuraId: INFURA_ID,
  appName: SITE_TITLE,
  jsonRpcUrl: ({ chainId }) =>
    chains.find((x) => x.id === chainId)?.rpcUrls?.[0] ??
    chain.mainnet.rpcUrls[0],
});

const connectors = connectorsForWallets(wallets);

const WPRainbow = (
  <RainbowKitProvider chains={chains}>
    <WagmiProvider connectors={connectors} provider={provider}>
      <WPRainbowConnect />
    </WagmiProvider>
  </RainbowKitProvider>
);

ReactDOM.render(WPRainbow, document.getElementById("wp-rainbow-button"));
