DROP TABLE IF EXISTS censusPopulation;
CREATE TABLE censusPopulation
(
    usps varchar(10),
    geoid varchar(20),
    pop10 int,
    hu10 int,
    aland int,
    awater int,
    aland_sqmi numeric(10,3),
    awater_sqmi numeric(10,3),
    intlat numeric(12,8),
    intlong numeric(12,8)
)
