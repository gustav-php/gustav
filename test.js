import http from "k6/http";
import { sleep } from "k6";

export const options = {
  discardResponseBodies: true,
  scenarios: {
    contacts: {
      executor: "shared-iterations",
      vus: 128,
      iterations: 20000,
      maxDuration: "30s",
    },
  },
};

export default function () {
  http.get("http://localhost:8080/cats/2");
}
