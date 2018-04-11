const gulp = require('gulp')
const plugins = require('gulp-load-plugins')()

const pkg = require('./package.json')

const argv = require('minimist')(process.argv.slice(2))
const babel = require('gulp-babel')
const eslint = require('gulp-eslint')
const gulpif = require('gulp-if')
const runSequence = require('run-sequence')

const paths = {
  src: './assets/',
  tmp: './.tmp/',
  dist: './dist/'
}

const options = {
  maps: !argv.production
}

/**
 * Scripts tasks.
 *
 * First lints JavaScript files using eslint, then compile them into a temporary
 * folder. Finally, generates sourcemaps (if `--production` option is not used)
 * and minify everything into the `dist` folder.
 */

gulp.task('scripts:lint', function () {
  return gulp.src(paths.src + 'scripts/**/*.js')
    .pipe(eslint())
    .pipe(eslint.format())
})

gulp.task('scripts:transpile', function () {
  return gulp.src(paths.src + 'scripts/**/*.js')
    .pipe(babel())
    .pipe(gulp.dest(paths.tmp + 'scripts/'))
})

gulp.task('scripts:minify', function () {
  return gulp.src(paths.tmp + 'scripts/*.js')
    .pipe(gulpif(options.maps,
      plugins.sourcemaps.init()
    ))
    .pipe(plugins.uglify({ output: { comments: 'license' } }))
    .pipe(plugins.rename({ suffix: '.min' }))
    .pipe(plugins.eol('\n'))
    .pipe(gulpif(options.maps,
      plugins.sourcemaps.write('.', { sourceRoot: paths.src + 'scripts/' })
    ))
    .pipe(gulp.dest(paths.dist + 'scripts/'))
})

gulp.task('scripts:build', function (callback) {
  runSequence(
    'scripts:lint',
    'scripts:transpile',
    'scripts:minify',
    callback)
})

/**
 * Build and watch task.
 *
 * Clean temporary and dist folders, then build everything.
 * The default task is `build`.
 */

gulp.task('clean', require('del').bind(null, [
  paths.tmp,
  paths.dist
]))

gulp.task('default', function () {
  gulp.start('build')
})

gulp.task('build', ['clean'], function (callback) {
  runSequence(
    'scripts:build',
    callback)
})

gulp.task('watch', function () {
  gulp.watch([paths.src + 'scripts/**/*.js'], ['scripts:build'])
})
