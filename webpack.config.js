const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    // .setManifestKeyPrefix('build/')

    // JS entry
    .addEntry('app', './assets/js/app.js')
    // CSS entry (optional but matches the guide)
    .addStyleEntry('style', './assets/css/app.css')

    .splitEntryChunks()
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    // If you later add Sass, uncomment:
    // .enableSassLoader()

    // .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
