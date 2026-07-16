UPDATE files
SET filedata = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(CAST(filedata AS CHAR CHARACTER SET utf8mb4), '&quot;', CHAR(34)),
                '&#039;', CHAR(39)
            ),
            '&lt;', '<'
        ),
        '&gt;', '>'
    ),
    '&amp;', '&'
)
WHERE filetype = 'txt';
