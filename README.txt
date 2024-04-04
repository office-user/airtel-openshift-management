Server => n1vl-pa-reporting.india.airtel.itm
File Permissions => sudo chown -R apache:apache /var/www/html;
Note => DB Username, DB Password, OCP Username & OCP Password are hard-coded in dbconfig.php & getfunction.php;

Mariadb Commands =>

USE openshiftdb;

CREATE TABLE dcList (
  dc VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL PRIMARY KEY,
  CHECK (dc REGEXP '^[A-Z0-9]{2,3}$')
);

INSERT INTO dcList (dc) VALUES ('N1');
INSERT INTO dcList (dc) VALUES ('N2');
INSERT INTO dcList (dc) VALUES ('MN');
INSERT INTO dcList (dc) VALUES ('PU');
INSERT INTO dcList (dc) VALUES ('BLR');

CREATE TABLE typeList (
  typeName VARCHAR(10) COLLATE utf8_bin NOT NULL PRIMARY KEY
);

INSERT INTO typeList (typeName) VALUES ('PROD');
INSERT INTO typeList (typeName) VALUES ('T&D');
INSERT INTO typeList (typeName) VALUES ('Staging');
INSERT INTO typeList (typeName) VALUES ('DMZ');
INSERT INTO typeList (typeName) VALUES ('Others');

CREATE TABLE clusterList (
  dc VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  cluster VARCHAR(24) COLLATE utf8_bin NOT NULL PRIMARY KEY,
  typeName VARCHAR(10) COLLATE utf8_bin NOT NULL,
  FOREIGN KEY (typeName) REFERENCES typeList(typeName),
  FOREIGN KEY (dc) REFERENCES dcList(dc)
) COLLATE = utf8_bin;

INSERT INTO clusterList (dc, cluster, typeName) VALUES ('BLR', 'blocp-pclus-01', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('BLR', 'blocp-pclus-02', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('BLR', 'blocp-pclus-03', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('BLR', 'blpl-drclus01', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'dartclus01n1', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('MN', 'mnocp-pclus01', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('MN', 'mnocp-pclus02', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-dclus-01', 'DMZ');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-dclus-02', 'DMZ');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-dclus-03', 'DMZ');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-01', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-02', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-03', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-04', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-05', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-pclus-06', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-sclus-01', 'Staging');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N1', 'n1ocp-tclus-01', 'T&D');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-dart-tclus-01', 'T&D');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-dclus-02', 'DMZ');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-dclus-03', 'DMZ');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-02', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-03', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-04', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-05', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-06', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-pclus-mgmnt', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('N2', 'n2ocp-tclus-01', 'T&D');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('PU', 'puocp-dart-pclus-01', 'PROD');
INSERT INTO clusterList (dc, cluster, typeName) VALUES ('PU', 'puocp-pclus-02', 'PROD');

CREATE TABLE projectList (
    dc VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    typeName VARCHAR(10) COLLATE utf8_bin NOT NULL,
    cluster VARCHAR(24) COLLATE utf8_bin NOT NULL,
    project VARCHAR(64) COLLATE utf8_bin NOT NULL,
    PRIMARY KEY (cluster, project),
    emailAddresses TEXT,
    numOfProdAvailability INT,
    numOfDMZAvailability INT,
    numOfSingleOrFailedReplicas INT,
    numWithMultipleReplicas INT,
    numOfMissingHPAs INT,
    numOfMissingAntiPodAffinities INT,
    numOfMissingRequiredAntiPodAffinities INT,
    numOfMissingLivenessProbes INT,
    numOfMissingReadinessProbes INT,
    numOfMissingStartupProbes INT,
    numOfStatefulsets INT,
    numOf3rdPartyImages INT,
    numOfExternalImages INT,
    numOfMissingIfNotPresentImagePullPolicies INT,
    lastUpdated TIMESTAMP NULL,
    FOREIGN KEY (dc) REFERENCES dcList(dc),
    FOREIGN KEY (typeName) REFERENCES typeList(typeName),
    FOREIGN KEY (cluster) REFERENCES clusterList(cluster)
) COLLATE = utf8_bin;

