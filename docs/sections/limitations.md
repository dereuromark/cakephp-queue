# Known Limitations

## Concurrent workers may execute the same job multiple times

If you want to use multiple workers, please double-check that all jobs have a high enough timeout (>> 2x max possible execution time of a job). Currently it would otherwise risk the jobs being run multiple times!

## Concurrent workers may execute the same job type multiple times

If you need limiting of how many times a specific job type can be run in parallel, you need to find a custom solution here.
