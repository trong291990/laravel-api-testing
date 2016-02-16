var gulp = require('gulp');
var sass = require('gulp-sass');
var minifyCss = require('gulp-minify-css');
var autoprefixer = require('gulp-autoprefixer');

var uglify = require('gulp-uglify');
var webpack = require("gulp-webpack");

var exec = require('child_process').exec;

/*
 * JS Task
 */
gulp.task('js', function() {
    return gulp.src('src/js/main.js')
        .pipe(webpack(require('./webpack.config.js'))).on('error', function(err) {
            console.log(err.toString());
            this.emit('end');
        })
        .pipe(uglify())
        .pipe(gulp.dest('dist/js/'));
});

/*
 * CSS Task
 */
gulp.task('css', function() {
    return gulp.src('src/sass/main.scss')
        .pipe(sass()).on('error', function(err) {
            console.log(err.toString());
            this.emit('end');
        })
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(minifyCss())
        .pipe(gulp.dest('dist/css/'));
});

gulp.task('jekyll', ['css', 'js'], function() {
    exec('jekyll build', function(err, stdout) {
        console.log(stdout);
    });
})

gulp.task('default', ['jekyll']);

gulp.task('watch', ['jekyll'], function() {
    gulp.watch('src/js/*', ['js']);
    gulp.watch('src/sass/*', ['css']);
});