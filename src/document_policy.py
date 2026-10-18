"""Visibility rules shared by SQL retrieval and the document catalogue.

Keep unlabelled local files searchable. Retired education records stay stored,
but are excluded even when only the document or chunk metadata has the label.
SQL aliases here are internal constants, never request input.
"""


def supported_domain_sql(alias: str) -> str:
    return f"COALESCE(UPPER({alias}.metadata->>'domain'), '') <> 'WASTE_EDUCATION'"


SEARCHABLE_DOCUMENT_SQL = " AND ".join([
    supported_domain_sql("d"),
    supported_domain_sql("c"),
    "COALESCE(LOWER(d.metadata->>'is_active'), 'true') NOT IN ('false', '0')",
    "COALESCE(LOWER(c.metadata->>'is_active'), 'true') NOT IN ('false', '0')",
])
