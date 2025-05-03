import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import fs from 'fs';

var themesInputsDefinitions = {
    /*'default': [
        'resources/sass/app.scss', 
        'resources/js/app.js',
        'resources/js/login.js',
        'resources/js/dashboard.js'
    ],
    'default2': [
        'resources/sass/app.scss', 
        'resources/js/app.js',
        'resources/js/login.js'
    ],
    'default3': [
        'resources/sass/app.scss', 
        'resources/js/app.js',
        'resources/js/login.js',
        'resources/js/dashboard.js'
    ]*/
}

var currentTheme = process.env.FZ_SELECTED_THEME;

var rootPath = null;
var runBuild = false;

if (process.argv.includes('build')) {
    rootPath = path.resolve('.');
    runBuild = true;
}
else {
    rootPath = path.resolve(`./resources/${currentTheme}/`);
}

var rootInputs = [];

if (runBuild) {
    themesInputsDefinitions[currentTheme].forEach((item) => {
        rootInputs.push('resources/' + currentTheme + '/' + item);
    });
}

function AdjustManifestPlugin() {
    let outDir, manifest, newObj;

    return {
        name: "adjust-manifest",
        configResolved(resolvedConfig) {
            outDir = resolvedConfig.build.outDir;

            const resolvedManifest = resolvedConfig.build.manifest;

            if (resolvedManifest) {
                manifest = typeof resolvedManifest === "string" ? resolvedManifest : false;
            } else {
                manifest = false;
            }
        },
        async writeBundle(_options, _bundle) {
            if (manifest === false) 
                return;

            var manifestFile = path.resolve(__dirname, outDir, manifest);
            var obj = null;

            fs.readFile(manifestFile, 'utf8', (err, data) => {

                if (err) 
                    throw err;
                
                obj = JSON.parse(data);
                newObj = {};

                Object.getOwnPropertyNames(obj).forEach((key) => {
                    let newKey = key.replaceAll(/^resources\/[^\/]*\/resources\//g, 'resources/');
                    newObj[newKey] = obj[key];
                });

                fs.writeFile(manifestFile, JSON.stringify(newObj), (err, data) =>{
                    if (err) 
                        throw err;
                });
            });
        },
    };
}

console.log(`

########## Selected theme "${currentTheme}" ##########

`);

export default defineConfig({
    root: rootPath,
    //base: 'resources/' + currentTheme,
    plugins: [
        laravel({
            input: rootInputs,
            refresh: true,
            hotFile: path.resolve('./public/hot')
        }),
        AdjustManifestPlugin()
    ],
    build: {
        minifest: 'manifest.json',
        outDir: 'public/' + currentTheme
    },
    server: {
        host: 'localhost',
        port: 8989,
        cors: true
        /*fs: {
            // Allow serving files from one level up to the project root
            allow: ['../..'],
        },*/
        /*base: './',
        proxy: {
            '^/resources/.*': {
                target: 'http://backend.external.space/',
                changeOrigin: true,
                rewrite: (xPath) => '/resources/js/login.js',
                secure: false,      
                ws: true,
                configure: (proxy, _options) => {
                    proxy.on('error', (err, _req, _res) => {
                        console.log('proxy error', err);
                    });
                    proxy.on('proxyReq', (proxyReq, req, _res) => {
                        console.log('Sending Request to the Target:', req.method, req.url);
                    });
                    proxy.on('proxyRes', (proxyRes, req, _res) => {
                        console.log('Received Response from the Target:', proxyRes.statusCode, req.url);
                    });
                },
            }
        }*/
    } 
});
