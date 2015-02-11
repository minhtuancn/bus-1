import sys

data = []
stop_names = ['stop1', 'stop2', 'stop3', 'stop4', 'stop5', 'stop6', 'stop7']
with open('raw-day-36.txt', 'r') as reader:
  line_count = 0
  for line in reader:
    line_count += 1
    is_am = line_count < 30
    fs = line.strip().split(" ")
    assert len(fs) == 7
    row = {}
    # Note: we omit the last column
    for i in range(6):
      if not ':' in fs[i]:
        continue
      if is_am:
        row[stop_names[i]] = fs[i]
      else:
        hour, minute = fs[i].split(':')
        hour = str(int(hour) + 12)
        row[stop_names[i]] = ':'.join((hour, minute))
    data.append(row)

with open('raw-night-36.txt', 'r') as reader:
  for line in reader:
    fs = line.strip().split(" ")
    assert len(fs) == 7
    row = {}
    for i in range(6):
      if not ':' in fs[i]:
        continue
      stop_index = i
      if i == 0:
        stop_index = 6
      if fs[i] == '-':
        continue
      hour, minute = fs[i].split(':')
      hour = str(int(hour) + 12)
      row[stop_names[stop_index]] = ':'.join((hour, minute))
    data.append(row)

print '$schedule = array();'
for row in data:
  print '$schedule[] = array(' + ', '.join(["'" + stop_name + "'=>'" + row[stop_name] + "'" for stop_name in stop_names if stop_name in row]) + ');'
