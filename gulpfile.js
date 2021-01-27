const { task, series, src, dest, watch } = require('gulp')
const zip = require('gulp-zip')
const { name } = require('./package.json')

const BUILD_ZIP_NAME = `${name}.ocmod.zip`
const SITE_PATH = process.env.SITE_DIR || './compiled-files'

// globs:
const GLOB_INSTALL_XML = './install.xml'
const GLOB_SOURCE_BASE = './upload/**'
// we dont need it at now as distribution is universal
// const SOURCE_TEMPLATES_EXCLUDE_GLOB = process.env.TARGET_PLATFORM === '2' ? `!${GLOB_SOURCE_BASE}/*.twig` : `!${GLOB_SOURCE_BASE}/*.tpl`
const GLOB_EXCLUDES = [
  '!**/.DS_Store',
  '!**/.git',
]

const copyFiles = () =>
  src([ GLOB_SOURCE_BASE, ...GLOB_EXCLUDES ])
    .pipe(dest(SITE_PATH))

const initWatcher = () =>
  watch([ GLOB_SOURCE_BASE, ...GLOB_EXCLUDES ], copyFiles)

const packFiles = () =>
  src([ GLOB_SOURCE_BASE, GLOB_INSTALL_XML, ...GLOB_EXCLUDES ], { base: '.' })
    .pipe(zip(BUILD_ZIP_NAME))
    .pipe(dest('./'))

/*
  Tasks:
*/
task('pack:files', packFiles)

task('copy:files', copyFiles)

task('watch:files', series(copyFiles, initWatcher))

task('build', packFiles)