from sys import argv, stderr, stdout
from csv import reader
from re import compile, I
from datetime import date

week_pat = compile(r'^week of (\d+)/(\d+) *- *(\d+)/(\d+)$', I)
person_pat = compile(r'^(\w+)( act(ual)?)?$', I)

today = date.today()

def esc(str):
    return str.replace("'", "''")

if __name__ == '__main__':
    
    data = open(argv[1], 'r')
    rows = reader(data)
    
    week_name, people = None, None
    
    for row in rows:
        
        if not row:
            continue
        
        is_date_row = bool(week_pat.match(row[0]))
        
        if is_date_row:
            week_match = week_pat.match(row[0])

            start_month, start_day, end_month, end_day \
                = [int(week_match.group(i), 10) for i in (1, 2, 3, 4)]

            start_date = date(today.year, start_month, start_day)
            start_week = start_date.strftime('%Y-W%U')

            end_date = date(today.year, end_month, end_day)
            end_week = end_date.strftime('%Y-W%U')
            
            assert start_week == end_week, 'Weeks in "%s" do not match.' % row[0]
            
            week_name = start_week
            continue
    
        if len(row) <= 3:
            continue
        
        is_people_row = min([bool(person_pat.match(val)) for val in row[1:-1]])
        
        if is_people_row:
            people = [person_pat.match(val).group(1) for val in row[1:-1]]
            continue
        
        if not row[0]:
            continue
        
        if week_name and people:
        
            client_name = row[0]
            
            for (person, days) in zip(people, row[1:]):

                if not days:
                    print >> stdout, "DELETE FROM utilization WHERE week='%s' AND client='%s' AND person='%s';" \
                                        % (esc(week_name), esc(client_name), esc(person))

                    continue
            
                if days == '*':
                    days = 0.0
                elif days in ('1/2', '*1/2', '1/2 *'):
                    days = 0.5
                elif days in ('1*', '1 *'):
                    days = 1.0
                else:
                    days = float(days)
                
                assert days <= 7, 'Too many days on %s for %s: %.1f' % (client_name, person, days)
                
                print >> stderr, week_name, client_name, person, days
                print >> stdout, "REPLACE INTO utilization SET week='%s', client='%s', person='%s', days=%.3f;" \
                                    % (esc(week_name), esc(client_name), esc(person), days)
