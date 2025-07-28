def DivExp(a, b):
    try:
        assert a > 0, "a should be greater than 0"
        c = a / b
    except AssertionError as ae:
        print(ae)
        sys.exit(0)
    except ZeroDivisionError:
        print("Value of b cannot be zero")
        sys.exit(0)
    else:
        return c