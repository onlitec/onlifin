import re

with open("src/db/api.ts", "r") as f:
    content = f.read()

# Pattern for simple query = query.is(...)
content = re.sub(
r"""[ \t]*if \(personId !== undefined\) \{\s*if \(personId === null\) \{\s*query = query\.is\('person_id', null\);\s*\} else \{\s*query = query\.eq\('person_id', personId\);\s*\}\s*\}""",
r"""    if (personId) {
      query = query.eq('person_id', personId);
    }""",
content)

# Pattern for multiple queries
content = re.sub(
r"""[ \t]*if \(personId !== undefined\) \{\s*if \(personId === null\) \{\s*accountsQuery = accountsQuery\.is\('person_id', null\);\s*cardsQuery = cardsQuery\.is\('person_id', null\);\s*transactionsQuery = transactionsQuery\.is\('person_id', null\);\s*\} else \{\s*accountsQuery = accountsQuery\.eq\('person_id', personId\);\s*cardsQuery = cardsQuery\.eq\('person_id', personId\);\s*transactionsQuery = transactionsQuery\.eq\('person_id', personId\);\s*\}\s*\}""",
r"""    // Filtrar por pessoa
    if (personId) {
      accountsQuery = accountsQuery.eq('person_id', personId);
      cardsQuery = cardsQuery.eq('person_id', personId);
      transactionsQuery = transactionsQuery.eq('person_id', personId);
    }""",
content)

with open("src/db/api.ts", "w") as f:
    f.write(content)

