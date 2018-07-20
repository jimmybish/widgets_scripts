<?php

$addrQuery =
"SELECT * FROM
(
SELECT s.CHAR_PREM_ID,
  p.GEO_CODE,
  p.ADDRESS2,
  (select SUBSTR(DESCR,instr(DESCR, ' ')+1, 99) from CISADM.CI_LOOKUP_VAL_L WHERE FIELD_NAME = 'HOUSE_TYPE' AND FIELD_VALUE = p.HOUSE_TYPE) TYPE,
  p.city,
  p.postal
FROM cisadm.ci_sa s,
  cisadm.ci_prem p
WHERE s.char_prem_id = p.prem_id
AND s.sa_status_flg IN ('20','30')
AND (END_DT         IS NULL
OR end_dt            > sysdate)
AND sa_type_cd       = 'T_RES_P'
AND NOT EXISTS (SELECT * FROM APEX.\"106_PREM_LATLONG\" l where l.PREM_ID = s.CHAR_PREM_ID)
ORDER BY s.CHAR_PREM_ID
)
WHERE ROWNUM <= ";


$addrCount = 
"SELECT COUNT(*) NUM
FROM cisadm.ci_sa s,
  cisadm.ci_prem p
WHERE s.char_prem_id = p.prem_id
AND s.sa_status_flg IN ('20','30')
AND (END_DT         IS NULL
OR end_dt            > sysdate)
AND sa_type_cd       = 'T_RES_P'
AND NOT EXISTS (SELECT * FROM APEX.\"106_PREM_LATLONG\" l where l.PREM_ID = s.CHAR_PREM_ID)
ORDER BY s.CHAR_PREM_ID";

$subQuery = 
"SELECT *
FROM APEX.\"106_SUBURBS\"
WHERE LATITUDE IS NULL
AND LONGITUDE IS NULL
AND SUBURB IS NOT NULL
AND ROWNUM <= ";

$subCount =
"SELECT COUNT(*) NUM
FROM APEX.\"106_SUBURBS\"
WHERE LATITUDE IS NULL
AND LONGITUDE IS NULL
AND SUBURB IS NOT NULL";

?>
