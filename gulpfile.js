var gulp = require('gulp');
var gutil = require('gulp-util');
var child_process = require('child_process');
var exec2 = require('child_process').exec;
var async = require('async');
var template = require('lodash.template');
var rename = require("gulp-rename");

var execute = function(command, options, callback) {
  if (options == undefined) {
    options = {};
  }
  command = template(command, options);
  if (!options.silent) {
    gutil.log(gutil.colors.green(command));
  }
  if (!options.dryRun) {
    if (options.env == undefined) {
      exec2(command, function(err, stdout, stderr) {
        gutil.log(stdout);
        gutil.log(gutil.colors.yellow(stderr));
        callback(err);
      });
    } else {
      exec2(command, {env: options.env}, function(err, stdout, stderr) {
        gutil.log(stdout);
        gutil.log(gutil.colors.yellow(stderr));
        callback(err);
      });
    }
  } else {
    callback(null);
  }
};

var start_server = function(options, cb) {
  var child = child_process.spawn(options.command, options.arguments, {
    detached: true,
    cwd: process.cwd,
    env: process.env,
    stdio: ['pipe', 'pipe', 'pipe']
//    stdio: ['ignroe', process.stdout, process.stderr]
//    stdio: ['ignore', 'pipe', 'pipe']
  });

  child.once('close', cb);
  child.unref();

  if (child.stdout) child.stdout.on('data', function(data) {
//    gutil.log(gutil.colors.yellow('boo '));
    gutil.log(gutil.colors.yellow(data));
//    console.log(data);
    var sentinal = options.sentinal;
    if (data.toString().indexOf(sentinal) != -1) {
      cb(null, child);
    }
  });
  if (child.stderr) child.stderr.on('data', function(data) {
    gutil.log(gutil.colors.red(data));
    var sentinal = options.sentinal;
    if (data.toString().indexOf(sentinal) != -1) {
      cb(null, child);
    }
  });
};

var paths = {
  src: ['htdocs/**/*.inc', 'htdocs/**/*.php', '!htdocs/vendor/**'],
  testE2E: ['tests/e2e/**/*.js'],
  testUnit: ['tests/php/**/*.php']
};

// livereload
var livereload = require('gulp-livereload');
var lr = require('tiny-lr');
var server = lr();

gulp.task('default', function() {
  // place code for your default task here
});

gulp.task('do-reload', function() {
  return gulp.src('htdocs/wp/index.php').pipe(livereload(server));
});

gulp.task('reload', function() {
  server.listen(35729, function(err) {
    if (err) {
      return console.log(err);
    }
    gulp.watch(paths.src, [ 'do-reload' ]);
  });
});

gulp.task('upload', function(cb) {
  var options = {
    dryRun: false,
    silent : false,
    src : "src",
    dest : "root@traintoproclaim.com:/var/www/virtual/traintoproclaim.com/html/htdocs/gospel/"
  };
  execute(
    'rsync -rzlt --chmod=Dug=rwx,Fug=rw,o-rwx --delete --exclude-from="upload-exclude.txt" --stats --rsync-path="sudo -u vu2006 rsync" --rsh="ssh" <%= src %>/ <%= dest %>',
    options,
    cb
  );
});

gulp.task('db-backup', function(cb) {
  var options = {
    dryRun: true,
    silent : false,
    dest : "root@traintoproclaim.com",
    key : "~/.ssh/dev_rsa",
    password : process.env.password
  };
  execute(
    'mysqldump -u cambell --password=<%= password %> saygoweb_fa | gzip > backup/current.sql.gz',
    options,
    cb
  );
});

gulp.task('db-restore', function(cb) {
  var options = {
    dryRun: true,
    silent : false,
    dest : "root@traintoproclaim.com",
    key : "~/.ssh/dev_rsa",
    password : process.env.password
  };
  execute(
    'gunzip -c backup/current.sql.gz | mysql -u cambell --password=<%= password %> -D saygoweb_fa',
    options,
    cb
  );
});

gulp.task('db-copy', function(cb) {
  var options = {
    dryRun : true,
    silent : false,
    dest : "root@traintoproclaim.com",
    key : "~/.ssh/dev_rsa",
    password : process.env.password
  };
  execute(
    'ssh -C -i <%= key %> <%= dest %> mysqldump -u cambell --password=<%= password %> saygoweb_fa | mysql -u cambell --password=<%= password %> -D saygoweb_fa',
    options,
    cb
  );
});

gulp.task('env-files', function() {
  gulp.src('tests/data/*.php')
    .pipe(gulp.dest('htdocs/'));
  gulp.src('tests/data/company/0/*.php')
  .pipe(gulp.dest('htdocs/company/0/'));
  gulp.src('tests/data/lang/*')
  .pipe(gulp.dest('htdocs/lang/'));
});

gulp.task('env-db', function(cb) {
  execute(
      'gunzip -c tests/data/fa_test.sql.gz | mysql -u travis -D fa_test',
      null,
      cb
    );
});

gulp.task('env-test', ['env-db', 'env-files'], function() {});

gulp.task('test-php', ['env-db'], function(cb) {
  var command = '/usr/bin/env php vendor/bin/phpunit -c phpunit.xml';
  execute(command, null, function(err) {
    cb(null); // Swallow the error propagation so that gulp doesn't display a nodejs backtrace.
  });
});

gulp.task('test-php-debug', ['env-db'], function(cb) {
  var options = {
      env: {'XDEBUG_CONFIG': 'idekey=eclipse'}
  };
  var command = '/usr/bin/env php vendor/bin/phpunit -c phpunit.xml';
  execute(command, options, function(err) {
    cb(null); // Swallow the error propagation so that gulp doesn't display a nodejs backtrace.
  });
});

gulp.task('test-php-coverage', ['env-db'], function(cb) {
  var command = '/usr/bin/env php vendor/bin/phpunit -c phpunit.xml --coverage-html ./wiki/code_coverage';
  execute(command, null, function(err) {
    cb(null); // Swallow the error propagation so that gulp doesn't display a nodejs backtrace.
  });
});

gulp.task('tasks', function(cb) {
  var command = 'grep gulp\.task gulpfile.js';
  execute(command, null, function(err) {
    cb(null); // Swallow the error propagation so that gulp doesn't display a nodejs backtrace.
  });
});

gulp.task('watch', function() {
  gulp.watch([paths.testUnit, paths.src], ['test-php']);
});
