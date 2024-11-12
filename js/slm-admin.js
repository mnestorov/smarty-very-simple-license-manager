function generateLicenseKey() {
    const key = [...Array(4)].map(() =>
        Math.random().toString(36).substring(2, 6).toUpperCase()
    ).join("-");
    document.getElementById("license_key").value = key;
}
