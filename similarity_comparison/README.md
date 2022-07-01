# gtn-jku-similarity-comparison
This project may be used to determine similarities 
of multiple examination questions of Moodle (https://moodle.org/) exams.  
Multiple similarity distance based algorithms are utilized 
for this process, currently [JaroWinkler](https://www.cs.cmu.edu/afs/cs/Web/People/wcohen/postscript/kdd-2003-match-ws.pdf)
and [SmithWatermanGotoh](https://www.sciencedirect.com/science/article/abs/pii/0022283682903989).  
Since string comparison scales well when utilizing multiple threads, 
a multithreaded implementation of the computationally intensive part is provided.

## Prerequisites
- PHP **8.0**
- `composer` dependency manager (https://getcomposer.org/)
- PHP extensions **gd** & **iconv** (enable in corresponding `php.ini`)
- Increase **memory_limit** (`php.ini`) to **>= 512M** for running the test cases

## Using the project 
1. (optional) Use the class [import/XlsImporter](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/import/XlsImporter.php) for importing a Moodle spreadsheet
2. For each data record (currently questions only), 
create a [model/RawRowEntity](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/model/RawRowEntity.php) using the concrete implementations of [model/AnswerRowEntity](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/model/AnswerRowEntity.php) and [model/QuestionRowEntity](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/model/QuestionRowEntity.php)
3. Instantiate and decide for a suitable strategy/algorithm to use for comparison,
e.g. see [strategy/ComparisonStrategyFactory](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/strategy/ComparisonStrategyFactory.php). Also pick a suitable threshold and other parameters.
4. pass the model from step 2. to a [QuestionOnlyComparator](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/comparator/QuestionOnlyComparator.php) 
5. (optional) perform a quick comparison between two strings using the strategy directly
6. retrieve the results for further processing/interpretation from the 
[QuestionOnlyComparator](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/src/comparator/QuestionOnlyComparator.php), see also the corresponding [test cases](https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison/src/branch/master/com.gtn-solutions/test)

# Appendix

## possible edit-based algorithms
- Levensthein distance
- Jaro Winkler distance  
  -> See https://github.com/iugrina/jarowinkler
- Smith Waterman Gotoh

## possible token set based algorithms
- Jaccard index
- Sorensen-Dice

## possible sequence based algorithms
- Ratcliff-Obershelp

## TODO
- should the algorithm separate between question categories (multi-answer, multi-choice, essay, etc.)?
- should question and answer be considered separately or combined when computing the similarity distance?