# Test speed

Runs Apache Benchmark to measure speed of application.

## Requirements:
- PHP 7.3 and higher
- [Apache Bench](https://httpd.apache.org/docs/2.4/programs/ab.html)
- Git repository (for *find slow commit* feature)

## Install

- `composer require janmikes/slowpoker`

## Features
- Clears cache
- Optimizes composer autoloader
- Cleans changes in git repository when done

## Usage

```
bin/test-speed [options]

Options:
      --maxSpeed=MAXSPEED  Max allowed response time, if not met (is higher) will exit as error
      --requests=REQUESTS  Number of HTTP requests made [default: 2000]
      --url=URL            URL to test speed with (glami.cz will be replaced with your working copy) [default: "http://www.glami.cz/damske-baleriny/?original"]
      --cacheDir=CACHEDIR  Relative path to cache directory [default: "temp/cache"]
```

## Usage with git bisect

This tool is great when used in git bisect, to detect which commit slowed the application.

#### Please note, that your working copy must be valid git repository (it must contain `.git` directory).

Run this in your working repository (working copy):

```
git bisect start <BAD> <GOOD>
git bisect run <TESTER>
```

Minimum example, when I know current commit is slow (80ms) and commit `647b912` was fast (72ms) and I want to find out which commit made it slower >73ms:
```
git bisect start HEAD 647b912
git bisect run /some-path/bin/speed-test --maxSpeed=73
```

--- 

This package was sponsored by [Glami](https://glami.cz), thank you!
