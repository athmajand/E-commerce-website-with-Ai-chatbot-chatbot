import pandas as pd

# Step 1: Create a DataFrame
data = {
    'Name': ['John', 'Maria', 'Liam', 'Sophia', 'Noah'],  # New names
    'Age': [23, 35, 45, 27, 32],
    'Salary': [60000, 52000, 72000, 48000, 91000],
    'Department': ['HR', 'IT', 'Finance', 'IT', 'HR']
}

df = pd.DataFrame(data)

# Step 2: Write the DataFrame to a CSV file
df.to_csv('employees.csv', index=False)
print("DataFrame saved to 'employees.csv'.")

# Step 3: Read the DataFrame from the CSV file
df_read = pd.read_csv('employees.csv')
print("\nDataFrame read from 'employees.csv':")
print(df_read)

# Step 4: Data Indexing, Selection, and Filtering

# Indexing: Select a specific column (Age)
print("\nIndexing: Select 'Age' column:")
print(df['Age'])

# Selection: Select multiple columns (Name and Department)
print("\nSelection: Select 'Name' and 'Department' columns:")
print(df[['Name', 'Department']])

# Row selection by index (select row at index 2)
print("\nSelect row at index 2:")
print(df.iloc[2])

# Filtering: Select rows where Age is greater than 30
print("\nFiltering: Rows where Age > 30:")
print(df[df['Age'] > 30])

# Filtering: Select rows where Department is 'IT'
print("\nFiltering: Rows where Department is 'IT':")
print(df[df['Department'] == 'IT'])
