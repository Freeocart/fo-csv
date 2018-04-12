const gulp = require('gulp'),
      zip = require('gulp-zip');

gulp.task('mktest', () => {
  gulp.src('./upload/**')
      .pipe(gulp.dest('../../oc2/'));
});

gulp.task('test', ['mktest'], () => {
  gulp.watch('./upload/**', () => {
    gulp.start('mktest');
  });
});

gulp.task('build', () => {
  gulp.src('./upload/*')
      .pipe(zip('foc_csv.ocmod.zip'))
      .pipe(gulp.dest('./'));
})