const cloudflareFallbackURLs = [
    "https://one.one.one.one/cdn-cgi/trace",
    "https://1.0.0.1/cdn-cgi/trace",
    "https://cloudflare-dns.com/cdn-cgi/trace",
    "https://cloudflare-eth.com/cdn-cgi/trace",
    "https://cloudflare-ipfs.com/cdn-cgi/trace",
    "https://workers.dev/cdn-cgi/trace",
    "https://pages.dev/cdn-cgi/trace",
    "https://cloudflare.tv/cdn-cgi/trace",
];

async function getCloudflareJSON() {
    let data = await fetchWithFallback(cloudflareFallbackURLs).then((res) =>
        res.text(),
    );
    let arr = data
        .trim()
        .split("\n")
        .map((e) => e.split("="));
    return Object.fromEntries(arr);
}

async function fetchWithFallback(links, obj) {
    let response;
    for (let link of links) {
        try {
            response = await fetch(link, obj);
            if (response.ok) return response;
        } catch (e) {}
    }
    return response;
}

export default getCloudflareJSON();
