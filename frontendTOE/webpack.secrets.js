module.exports = (configs, secretsFile) => {
    try {
        const secrets = require(secretsFile);
        Object.assign(configs, secrets);
    } catch(e) {
        Object.assign(configs, {
            GOOGLE_MAPS_API_KEY: "MISSING_SECRETS"
        })
    }

    return configs
};
